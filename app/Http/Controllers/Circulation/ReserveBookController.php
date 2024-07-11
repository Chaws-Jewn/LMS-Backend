<?php

namespace App\Http\Controllers\Circulation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\BorrowMaterialController;
//use App\Models\Reservation;
use App\Models\BorrowMaterial;
use App\Models\User;
use App\Models\Material;
use App\Models\Patron;
use Exception;
use Storage;

class ReserveBookController extends Controller
{
    public function reservebook(Request $request)
{
    // Decode the JSON payload from the request body
    $payload = json_decode($request->getContent());

    try {
        // Check if the book_id exists in the materials table
        $material = Material::find($payload->book_id);
        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        // Check if the material status allows reservation
        if ($material->status == 0) {
            return response()->json(['error' => 'Book is currently borrowed'], 400);
        }


        if ($material->status == 3) {
            return response()->json(['error' => 'Book has not been returned'], 400);
        }

        // User and patron information
        $user = User::find($payload->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Retrieve patron information if user has a patron_id
        $patron = null;
        if ($user->patron_id) {
            $patron = Patron::find($user->patron_id);
        }

        if (!$patron) {
            return response()->json(['error' => 'Patron not found'], 404);
        }

        // Number of materials allowed for this patron
        $materialsAllowed = $patron->materials_allowed;

        // Allowed number of active reservations
        $activeReservationsCount = BorrowMaterial::where('user_id', $payload->user_id)
                                            ->where('status', 0) // Assuming status 1 means active reservation
                                            ->count();

        if ($activeReservationsCount >= $materialsAllowed) {
            return response()->json(['error' => 'User already has the maximum number of active reservations allowed'], 400);
        }

        // Use a transaction to ensure both operations happen at the same time
        DB::beginTransaction();

        // Create a new BorrowMaterial instance (reservation)
        $reservation = new BorrowMaterial();
        $reservation->book_id = $payload->book_id;
        $reservation->user_id = $payload->user_id;
        $reservation->reserve_date = now(); // Timestamp of reservation creation
        $reservation->reserve_expiration = $payload->reserve_expiration;
        $reservation->fine = 0; // Initial fine set to 0 for reservation
        $reservation->reservation_type = 1; //assuming 1 is walk-in
        $reservation->status = 1; // Status 1 for pending reservation

        // Save the reservation record
        $reservation->save();

        // Update the material status to indicate it's reserved
        $material->status = 2; // Update with appropriate status value
        $material->save();

        // Commit the transaction
        DB::commit();

        // Prepare and return the response data
        $data = ['reservation' => $reservation];
        return response()->json($data);
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        DB::rollBack();
        return response()->json(['error' => 'An error occurred while processing the reservation', 'details' => $e->getMessage()], 500);
    }
}

    public function reservelist(Request $request, $type = null) {
        // Fetch all reservation data from the reservations table
        $reservelist = BorrowMaterial::with(['user.program', 'user.patron'])
                        ->whereHas('user', function($query) {
                            $query->where('status', 1);
                        });
    
        if ($type === 'online') {
            $reservelist->where('type', 'online');
        } elseif ($type === 'walk-in') {
            $reservelist->where('type', 'walk-in');
        }
        
        // Include the id field along with other fields for queue data
        $queueData = $reservelist->orderBy('book_id')
            ->orderBy('reserve_date', 'asc')
            ->get(['id', 'user_id', 'book_id', 'reserve_date', 'status']);
    
        // Initialize an array to keep track of queue positions for each book
        $bookQueuePositions = [];
    
        // Iterate through each reservation to calculate queue positions
        foreach ($queueData as $reservation) {
            // Check if reservation status is 0 (not active)
            if ($reservation->status == 0) {
                continue; // Skip reservations with status 0
            }
    
            // Get the book ID
            $bookId = $reservation->book_id;
    
            // If the book ID is not yet in the bookQueuePositions array, initialize its queue position to 1
            if (!isset($bookQueuePositions[$bookId])) {
                $bookQueuePositions[$bookId] = 1;
            }
    
            // Set the queue position for the current reservation
            $reservation->queue_position = $bookQueuePositions[$bookId];
    
            // Increment the queue position for the next reservation
            $bookQueuePositions[$bookId]++;
        }
    
        // Return the combined result as JSON
        return response()->json($queueData);
    }
    
    public function queue(Request $request) {
        // Fetch all queue data from the reservations table
        $queueData = Reservation::orderBy('book_id')
            ->orderBy('start_date', 'asc')
            ->get(['user_id', 'book_id', 'start_date', 'status']); // Include the status field
    
        // Initialize an array to keep track of queue positions for each book
        $bookQueuePositions = [];
    
        // Iterate through each reservation to calculate queue positions
        foreach ($queueData as $reservation) {
            // Check if reservation status is 0 (not active)
            if ($reservation->status == 0) {
                continue; // Skip reservations with status 0
            }
    
            // Get the book ID
            $bookId = $reservation->book_id;
    
            // If the book ID is not yet in the bookQueuePositions array, initialize its queue position to 1
            if (!isset($bookQueuePositions[$bookId])) {
                $bookQueuePositions[$bookId] = 1;
            }
    
            // Set the queue position for the current reservation
            $reservation->queue_position = $bookQueuePositions[$bookId];
    
            // Increment the queue position for the next reservation
            $bookQueuePositions[$bookId]++;
        }
    
        // Return the result as JSON (or any other format required by the front end)
        return response()->json($queueData);
    }
    
    public function getQueuePosition(Request $request) {
        // Get the authenticated user's ID
        $userId = $request->user()->id;
    
        // Fetch all active reservations for the user's books
        $userReservations = Reservation::where('user_id', $userId)
                            ->where('status', '!=', 0) // Exclude reservations with status 0
                            ->orderBy('start_date')
                            ->get(['book_id', 'start_date']);
    
        // Initialize the queue positions
        $queuePositions = [];
    
        // Process the user's reservations and determine the queue position for each book
        foreach ($userReservations as $userReservation) {
            $bookId = $userReservation->book_id;
    
            // Count the number of reservations with earlier start dates for the same book
            $position = Reservation::where('book_id', $bookId)
                            ->where('start_date', '<', $userReservation->start_date)
                            ->where('status', '!=', 0) // Exclude reservations with status 0
                            ->count() + 1; // Add 1 to start positions from 1
    
            // Assign the queue position for the book
            $queuePositions[$bookId] = $position;
        }
    
        return response()->json($queuePositions);
    }

    public function destroy ($id){
        // Find the reservation by ID
        $reservation = Reservation::find($id);

        // Check if the reservation exists
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Attempt to delete the reservation
        try {
            $reservation->delete();
            return response()->json(['message' => 'Reservation deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete reservation', 'error' => $e->getMessage()], 500);
        }
    }
    
}


//edited out
// // Function for reserving books
    // public function reservebook(Request $request)
    // {
    //     // Validate incoming request
    //     $request->validate([
    //         'user_id' => 'required|integer',
    //         'book_id' => 'required|exists:books,id',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date',
    //         'status' => 'required|boolean',
    //     ]);

    //     // Get the BorrowMaterialController instance
    //     $borrowMaterialController = new BorrowMaterialController();

    //     // Check if user_id exists in the $users array
    //     $userExists = $borrowMaterialController->checkUserExists($request->user_id);

    //     if (!$userExists) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

    //     // Proceed with reservation

    //     $reserve = new Reservation();
    //     $reserve->user_id = $request->user_id;
    //     $reserve->book_id = $request->book_id;
    //     $reserve->start_date = $request->start_date; // Corrected field name
    //     $reserve->end_date = $request->end_date;
    //     $reserve->status = $request->status;
        
    //     error_log($reserve);
    //     $reserve->save();

    //     return response()->json($reserve);
    // }

    // // Create a new BorrowMaterial instance
        // $reservation = new Reservation();
        // // Fill the BorrowMaterial instance with request data excluding 'book_id'
        // $reservation->fill($request->all());
        
        // // Save the BorrowMaterial instance
        // $reservation->save();
        
        // $data = ['Reservation' => $reservation];
        // return response()->json($data);



 // public function reservelist(Request $request){
    //     $reservelist = Reservation::with(['user.program', 'user.department', 'user.patron'])
    //                     ->whereHas('user', function($query){
    //                         $query->where('status', 1);
    //                     })
    //                     ->get();
    //     return response()->json($reservelist);
    // }