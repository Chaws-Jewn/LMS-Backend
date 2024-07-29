<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BorrowMaterial;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Program;
use App\Models\Patron;
use App\Models\Material;
use Exception;
use Carbon\Carbon;
use Storage;

/*  
    0 => already borrowed?
    1=> available
    2 => missing or reserved?
    3 => unreturned
    4 => unlabeled
*/
    
class BorrowMaterialController extends Controller
{
    const URL = 'http://26.68.32.39:8000';
    public function borrowbook(Request $request)
    {
        $payload = $request->all();
        $logMessages = [];
        $logMessages[] = 'Received payload: ' . json_encode($payload);
        Log::info('Received payload:', $payload);
        
        // Check if the required fields are present
        $requiredFields = ['book_id', 'user_id', 'borrow_date', 'borrow_expiration', 'fine', 'isChecked'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                return response()->json(['error' => 'Missing required field: ' . $field, 'logMessages' => $logMessages], 400);
            }
        }

        // Check if the book_id exists in the materials table
        $material = Material::find($payload['book_id']);
        if (!$material) {
            return response()->json(['error' => 'Book cannot be found'], 404);
        }

        // Check if the material status allows borrowing || 1 = available
        if ($material->status !== 0) {
            return response()->json(['error' => 'Book is currently borrowed or not available'], 400);
        }

        // User and patron information
        $user = User::find($payload['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $patron = Patron::find($user['patron_id']);
        if (!$patron) {
            return response()->json(['error' => 'Patron type not found'], 404);
        }

        // Number of materials allowed for this patron
        $materialsAllowed = $patron->materials_allowed;

        // Allowed number of active borrows
        $activeBorrowsCount = BorrowMaterial::where('user_id', $payload['user_id'])
                                            ->where('status', 1) // Assuming status 1 means active borrowed books
                                            ->count();

        if ($activeBorrowsCount >= $materialsAllowed) {
            return response()->json(['error' => 'User already has the maximum number of active borrows allowed'], 400);
        }

        // Use a transaction to ensure both operations happen at the same time
        DB::beginTransaction();
        try {
            // Create a new BorrowMaterial instance
            $borrowMaterial = new BorrowMaterial();
            $borrowMaterial->book_id = $payload['book_id'];
            $borrowMaterial->user_id = $payload['user_id'];
            $borrowMaterial->fine = $payload['fine'];
            $borrowMaterial->borrow_expiration = $payload['borrow_expiration'];
            $borrowMaterial->borrow_date = $payload['borrow_date'];
            $borrowMaterial->status = 1; // Assuming status 1 means active borrow
            $borrowMaterial->save();

            // Update the material status
            $material->status = 1; // Update with appropriate status value
            $material->save();

            // Log the borrowing activity
            // system - worker - position - || created borrow instance for || username - student id || with || book title 
            $log = new ActivityLogController();

            $logParam = new \stdClass(); // Instantiate stdClass
            $currentUser = Auth::user();
            $logParam->system = 'Circulation';
            $logParam->username = $currentUser->username;
            $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
            $logParam->position = $currentUser->position;
            $logParam->desc = 'Borrow instance for  '. $user->first_name . ' '. $user->last_name . ' with book ' . $payload['book_id'];

            $log->savePersonnelLog($logParam);

            // Commit the transaction
            DB::commit();

            $data = ['borrow_material' => $borrowMaterial];
            return response()->json($data);
        }   catch (Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while borrowing the material', 'details' => $e->getMessage()], 500);
        }
    }

    public function fromreservation(Request $request, $id)
{
    // Set the borrow_date to now and borrow_expiration to one week later
    $borrow_date = Carbon::now();
    $borrow_expiration = Carbon::now()->addWeek();

    // Find the record in the BorrowMaterial table by id
    $borrowMaterial = BorrowMaterial::find($id);

    // Check if the record exists
    if (!$borrowMaterial) {
        return response()->json(['message' => 'Record not found'], 404);
    }

    // Get the book_id from the request data and find the material
    $material = Material::find($request->input('book_id'));

    // Check if the book exists
    if (!$material) {
        return response()->json(['error' => 'Book not found'], 404);
    }

    // Check if the book is available for borrowing 
    if ($material->status != 0) {
        return response()->json(['error' => 'This book is currently borrowed or not available'], 403);
    }

    // Get the queue_position from the request data
    $queuePosition = $request->input('queue_position');
    \Log::info('Received queue_position:', ['queue_position' => $queuePosition]);

    // Check if the queue_position is 1
    if ($queuePosition != '1') {
        return response()->json(['message' => 'Only items with queue position 1 can be borrowed'], 400);
    }

    // Update the borrow_date, borrow_expiration, and status in BorrowMaterial table
    $borrowMaterial->update([
        'borrow_date' => $borrow_date,
        'borrow_expiration' => $borrow_expiration,
        'status' => 1,
    ]);

    // Update the status of the material in the Material table to 1 (borrowed)
    $material->update(['status' => 1]);

    // Return a response
    return response()->json(['message' => 'Record updated successfully'], 200);
}
        
        
    public function borrowlist(Request $request)
    {
        $borrowMaterial = BorrowMaterial::with('user.student_program')
                            ->whereHas('user', function($query) {
                                $query->where('status', 1);
                            })
                            ->whereNotNull('borrow_date')
                            ->get();

        return response()->json($borrowMaterial->map(function($borrowMaterial){
            return[
                'id' => $borrowMaterial->id,
                'user_id' => $borrowMaterial->user->id,
                'name' => $borrowMaterial->user->last_name,
                'email' => $borrowMaterial->user->domain_email,
                'department' => $borrowMaterial->user->student_program->department_short,
                'program' => $borrowMaterial->user->student_program->program_short,
                'title' => $borrowMaterial->material->title,
                'accession' => $borrowMaterial->material->accession,
                'status' => $borrowMaterial->status,
                'borrow_date' => $borrowMaterial->borrow_date,
                'borrow_expiration' => $borrowMaterial->borrow_expiration,
                'fine' => $borrowMaterial->fine,
            ];
        }));
    }

    public function returnedlist(Request $request){
        $borrowMaterial = BorrowMaterial::with(['user', 'user.student_program'])
                            ->whereHas('user', function($query){
                                $query->where('status', 0);
                            })
                            ->whereNotNull('date_returned')
                            ->get();
        return response()->json($borrowMaterial->map(function($borrowMaterial){
            return[
                'id' => $borrowMaterial->id,
                'fname' => $borrowMaterial->user->first_name,
                'lname' => $borrowMaterial->user->last_name,
                'email' => $borrowMaterial->user->domain_email,
                'department' => $borrowMaterial->user->student_program->department_short,
                'program' => $borrowMaterial->user->student_program->program_short,
                'date_borrowed' => $borrowMaterial->borrow_date,
                'date_returned' => $borrowMaterial->date_returned,
                'title' => $borrowMaterial->material->title,
                'status' => $borrowMaterial->status,
                'fine' => $borrowMaterial->fine,
            ];
        }));
    }

    public function returnedlistid($id)
    {
        $returnedItems = BorrowMaterial::with('book')
                                    ->where('user_id', $id)
                                    ->where('status', 0) // Assuming 0 represents returned status
                                    ->get();
                                    
        // Count the total number of returned books
        $totalReturnedBooks = $returnedItems->count();
        
        if ($returnedItems->isEmpty()) {
            return response()->json(['message' => 'No returned items found for this user'], 404);
        }
        
        // Append the title of the book to each returned item
        foreach ($returnedItems as $item) {
            $item->title = $item->book->title;
        }
        
        // Return the response with the updated returned items and total count
        return response()->json([
            'returnedItems' => $returnedItems,
            'totalReturnedBooks' => $totalReturnedBooks
        ], 200);
    }

    public function borrowEdit(Request $request)
    {
        $payload = json_decode($request->payload);
  
        $borrowMaterial = BorrowMaterial::find($payload->id);
    
        if (!$borrowMaterial) {
            return response()->json(['error' => 'Borrow material not found'], 404);
        }

        $borrowMaterial->book_id = $payload->book_id;
        $borrowMaterial->user_id = $payload->user_id;
        $borrowMaterial->fine = $payload->fine;
        $borrowMaterial->borrow_expiration = $payload->borrow_expiration;
        $borrowMaterial->borrow_date = $payload->borrow_date;

        // Save the updated record
        $borrowMaterial->save();

        // Return the updated record as a response
        $data = ['borrow_material' => $borrowMaterial];
        return response()->json($data);
    }   

    public function borrowcount(Request $request, $id)
    {
        $user = User::find($id);
        $activeBorrowsCount = BorrowMaterial::where('user_id', $id)
                                    ->where('status', 1)
                                    ->count();
        return response()->json(['active_borrows_count' => $activeBorrowsCount]);
    }

    public function returnbook(Request $request, $id)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Find the BorrowMaterial by its ID
            $borrowMaterial = BorrowMaterial::find($id);

            // Check if the borrowed material exists
            if (!$borrowMaterial) {
                // Rollback the transaction if the borrowed material is not found
                DB::rollback();
                return response()->json(['error' => 'Borrowed material not found'], 404);
            }

            // Find the Material (assuming it represents a book or item being borrowed)
            $material = Material::find($borrowMaterial->book_id);

            // Check if the material exists
            if (!$material) {
                // Rollback the transaction if the material is not found
                DB::rollback();
                return response()->json(['error' => 'Material not found'], 404);
            }

            // Set material status to 'available'
            $material->status = 0;

            // Save the changes to the Material
            $material->save();

            // Update BorrowMaterial status and return date
            $borrowMaterial->status = 0; // Assuming status 0 means returned
            $borrowMaterial->date_returned = now();

            // Save the changes to the BorrowMaterial
            $borrowMaterial->save();

            // Commit the transaction if all operations succeed
            DB::commit();

            // Return a success response
            return response()->json(['message' => 'Material returned successfully'], 200);
        } catch (Exception $e) {
            // Rollback the transaction if any operation fails
            DB::rollback();

            // Handle the exception, log the error or return an error response
            return response()->json(['error' => 'An error occurred while returning the material', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        // Find the record
        $borrowMaterial = BorrowMaterial::find($id);
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }
        if (!$borrowMaterial) {
            return response()->json(['error' => 'BorrowMaterial not found'], 404);
        }
        $book->available = 1;
        $borrowMaterial->delete();
        return response()->json(['message' => 'BorrowMaterial deleted successfully']);
    }
    
        
    public function getByUserId(Request $request, $userId)
    {
        $borrowMaterial = BorrowMaterial::with('book')->where('user_id', $userId)
                                        ->orderBy('borrow_expiration', 'asc')
                                        ->get();

        foreach($borrowMaterial as $book) {
            if($book->book->image_url != null)
                $book->book->image_url = self::URL . Storage::url($book->book->image_url);

            // $book->book->authors = json_decode($book->book->authors);
            // $book->book->authors = 'sup';
        }

        if ($borrowMaterial->isEmpty()) {
            return response()->json(['message' => 'No borrow records found for the user'], 404);
        }

        return response()->json($borrowMaterial, 200);
    }
}

