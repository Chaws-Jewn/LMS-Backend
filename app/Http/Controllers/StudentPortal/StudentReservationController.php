<?php

namespace App\Http\Controllers\StudentPortal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BorrowMaterial;
use App\Models\Reservation; // Import the Reservation model
use App\Models\Material; // Import the Material model
use App\Models\User; // Import the User model
use App\Models\Patron; // Import the Patron model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//new
use Exception;


class StudentReservationController extends Controller
{
    public function reservebook(Request $request)
    {
        $payload = $request->all();
        $logMessages = [];
        $logMessages[] = 'Received payload: ' . json_encode($payload);
        Log::info('Received payload:', $payload);
    
        // Check if the required fields are present
        $requiredFields = ['book_id', 'user_id', 'reserve_date', 'price', 'status', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                return response()->json(['error' => 'Missing required field: ' . $field, 'logMessages' => $logMessages], 400);
            }
        }
    
        try {
            // Check if the book_id exists in the materials table
            $material = Material::find($payload['book_id']);
            if (!$material) {
                return response()->json(['error' => 'Material not found'], 404);
            }
    
            // User and patron information
            $user = User::find($payload['user_id']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            // Patron information
            $patron = Patron::find($user->patron_id);
            if (!$patron) {
                return response()->json(['error' => 'Patron not found'], 404);
            }
    
            // Number of materials allowed for this patron
            $materialsAllowed = $patron->materials_allowed;
    
            // Allowed number of active reservations for the user
            $activeReservationsCount = Reservation::where('user_id', $payload['user_id'])
                ->where('status', 0) // 1 means active reservation
                ->count();
    
            if ($activeReservationsCount >= 3) {
                return response()->json(['error' => 'User already has the maximum number of active reservations allowed'], 400);
            }
    
            // Use a transaction to ensure both operations happen at the same time
            DB::beginTransaction();
            try {
                // Check if the user already has an active reservation for this book
                $existingReservation = Reservation::where('user_id', $payload['book_id'])
                    ->where('status', 0) // Active reservation status
                    ->exists();
    
                if ($existingReservation) {
                    return response()->json(['error' => 'There is already an active reservation for this book'], 400);
                }
    
                // Create a new Reservation instance (reservation)
                $reservation = new Reservation();
                $reservation->book_id = $payload['book_id'];
                $reservation->user_id = $payload['user_id'];
                $reservation->reserve_date = $payload['reserve_date'];
                $reservation->fine = $payload['price'];
                $reservation->status = $payload['status'];
                $reservation->reservation_type = $payload['type'];
    
                $reservation->save();
    
                // Update the material status to indicate it's reserved
                // Note: You may need to adjust this logic based on your application flow
                if ($material->status != 0 && $material->status != 3) {
                    $material->status = 2; // Update with appropriate status value
                    $material->save();
                }
    
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
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while processing the reservation', 'details' => $e->getMessage()], 500);
        }
    }

    public function getQueuePosition(Request $request) 
    {
        // Get the authenticated user's ID
        $userId = $request->user()->id;
    
        // Fetch all active reservations for the user's books
        $userReservations = Reservation::where('user_id', $userId)
                            ->where('status', '=', 0) // Exclude reservations with status 0
                            ->orderBy('reserve_date')
                            ->get(['book_id', 'reserve_date']);
    
        // Initialize the queue positions
        $queuePositions = [];
    
        // Process the user's reservations and determine the queue position for each book
        foreach ($userReservations as $userReservation) {
            $bookId = $userReservation->book_id;
    
            // Count the number of reservations with earlier start dates for the same book
            $position = Reservation::where('book_id', $bookId)
                            ->where('reserve_date', '<', $userReservation->reserve_date)
                            ->where('status', '=', 0) // Exclude reservations with status 0
                            ->count() + 1; // Add 1 to start positions from 1
    
            // Assign the queue position for the book
            $queuePositions[$bookId] = $position;
        }
    
        return response()->json($queuePositions);
    }
    

    //get reservation for user 

    public function getReservationsByUserId($user_id)
{
    try {
        // Fetch all active reservations for the user's books
        $userReservations = Reservation::where('user_id', $user_id)
                            ->where('status', '=', 0) // Exclude reservations with status 0
                            ->orderBy('reserve_date')
                            ->get(['book_id', 'reserve_date', 'status', 'id']);
        
        // Initialize the queue positions and book details
        $queuePositions = [];
        $bookDetails = [];

        // Process the user's reservations and determine the queue position for each book
        foreach ($userReservations as $userReservation) {
            $bookId = $userReservation->book_id;

            // Get book details
            $bookDetail = Material::find($bookId);

            // Add book details to the array
            $bookDetails[$bookId] = $bookDetail;

            // Count the number of reservations with earlier start dates for the same book
            $position = Reservation::where('book_id', $bookId)
                            ->where('reserve_date', '<', $userReservation->reserve_date)
                            ->where('status', '=', 0) // Exclude reservations with status 0
                            ->count() + 1; // Add 1 to start positions from 1

            // Assign the queue position for the book
            $queuePositions[$bookId] = $position;
        }

        // Return the reservations along with their queue positions and book details
        return response()->json([
            'reservations' => $userReservations,
            'queue_positions' => $queuePositions,
            'book_details' => $bookDetails
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch reservations', 'details' => $e->getMessage()], 500);
    }
}

    public function deleteReservation($id)
    {
        $reservation = Reservation::find($id);

        if ($reservation) {
            $reservation->delete();
            return response()->json(['message' => 'Reservation deleted successfully']);
        } else {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
    }
    
    public function getBorrowedByUserId($user_id)
{
    try {
        // Retrieve reserved materials where status is 1
        $borrowedMaterials = BorrowMaterial::where('user_id', $user_id)
                            ->where('status', 1)
                            ->get();

        // Load relationships for each borrowed material
        $borrowedMaterials->load('material', 'user');

        // Adjust the response format as needed, ensuring it returns an array
        return response()->json(['borrowedMaterials' => $borrowedMaterials]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch borrowed materials', 'details' => $e->getMessage()], 500);
    }
}
    public function getBorrowedById($id)
{
    try {
        // Find the borrowed material by ID where status is 1
        $borrowedMaterial = BorrowMaterial::where('id', $id)
                            ->where('status', 1)
                            ->first();

        if (!$borrowedMaterial) {
            return response()->json(['error' => 'Borrowed material not found or status is not approved'], 404);
        }

        // Optionally, you can eager load related data (e.g., book details)
        $borrowedMaterial->load('material', 'user');

        // Return the borrowed material data as JSON response
        return response()->json(['borrowedMaterial' => $borrowedMaterial]);
    } catch (Exception $e) {
        // Handle any unexpected errors
        Log::error('Error fetching borrowed material by ID: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch borrowed material', 'details' => $e->getMessage()], 500);
    }
}

    public function viewReservationById($id)
    {
        // Eager load the 'book' relationship and 'user' relationship if needed
        $reservation = Reservation::with('book')->find($id);
    
        if ($reservation) {
            // Return the reservation with associated book details as JSON response
            return response()->json($reservation);
        } else {
            // Return a JSON response with HTTP status 404 (Not Found) if reservation not found
            return response()->json(['error' => 'Reservation not found'], 404);
        }
    }
}
