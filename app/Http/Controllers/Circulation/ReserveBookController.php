<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\BorrowMaterialController;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Book;
use Exception;
use Storage;

class ReserveBookController extends Controller
{
    public function reservebook(Request $request){
        
        $payload=json_decode($request->payload);

        // Check if the book_id exists in the books table
        $book = Book::find($payload->book_id);
        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        // Check if the user already has a reservation with status 1 on the same book
        $existingReservation = Reservation::where('book_id', $payload->book_id)
        ->where('user_id', $payload->user_id)
        ->whereHas('user', function ($query) {
            $query->where('status', 1);
        })
        ->exists();

        if ($existingReservation) {
            return response()->json(['error' => 'User already has a reservation for this book'], 400);
        }
    
        // Create Reservation instance
        $reservation = new reservation();
        $reservation -> book_id = $payload->book_id;
        $reservation -> user_id = $payload->user_id;
        $reservation -> start_date = $payload->start_date;
        $reservation -> end_date = $payload->end_date;
        $reservation -> type = "walk-in";
        // $reservation -> date_of_expiration= $payload->date_of_expiration;
        $reservation -> save();

        $data = ['Reservation' => $reservation];
        return response()->json($data);
    }

    // public function reservelist(Request $request){
    //     $reservelist = Reservation::with(['user.program', 'user.department', 'user.patron'])
    //                     ->whereHas('user', function($query){
    //                         $query->where('status', 1);
    //                     })
    //                     ->get();
    //     return response()->json($reservelist);
    // }

    public function reservelist(Request $request, $type = null) {
        // Fetch all reservation data from the reservations table
        $reservelist = Reservation::with(['user.program.department', 'user.patron'])
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
            ->orderBy('start_date', 'asc')
            ->get(['id', 'user_id', 'book_id', 'start_date', 'status']);
    
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
