<?php

namespace App\Http\Controllers\StudentPortal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $requiredFields = ['book_id', 'user_id', 'reserve_date', 'reserve_expiration', 'price', 'status', 'type'];
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
    
            // Check if the material status allows reservation
            if ($material->status == 0) {
                return response()->json(['error' => 'Book is currently borrowed'], 400);
            }
    
            if ($material->status == 3) {
                return response()->json(['error' => 'Book has not been returned'], 400);
            }
    
            // User and patron information
            $user = User::find($payload['user_id']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            $patron = Patron::find($user->patron_id);
            if (!$patron) {
                return response()->json(['error' => 'Patron not found'], 404);
            }
    
            // Number of materials allowed for this patron
            $materialsAllowed = $patron->materials_allowed;
    
            // Allowed number of active reservations
            $activeReservationsCount = Reservation::where('user_id', $payload['user_id'])
                                                    ->where('status', 1) // 1 means active reservation
                                                    ->count();
    
            if ($activeReservationsCount >= $materialsAllowed) {
                return response()->json(['error' => 'User already has the maximum number of active reservations allowed'], 400);
            }
    
            // Use a transaction to ensure both operations happen at the same time
            DB::beginTransaction();
            try {
                // Create a new Reservation instance (reservation)
                $reservation = new Reservation();
                $reservation->book_id = $payload['book_id'];
                $reservation->user_id = $payload['user_id'];
                $reservation->reserve_date = $payload['reserve_date'];
                $reservation->reserve_expiration = $payload['reserve_expiration'];
                $reservation->fine = $payload['price'];
                $reservation->status = $payload['status'];
                $reservation->reservation_type = $payload['type'];

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
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred while processing the reservation', 'details' => $e->getMessage()], 500);
        }
    }

    //get reservation for user 

    public function getReservationsByUserId($user_id)
    {
        try {
            $reservations = Reservation::where('user_id', $user_id)->get();
            
            // Adjust the response format as needed, ensuring it returns an array
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch reservations', 'details' => $e->getMessage()], 500);
        }
    }

    //reservation by id di pa gumagana

    public function getReservationById($reservationId)
    {
        try {
            // Find the reservation by ID
            $reservation = Reservation::find($reservationId);

            if (!$reservation) {
                return response()->json(['error' => 'Reservation not found'], 404);
            }

            // Optionally, you can eager load related data (e.g., book details)
            $reservation->load('book');

            // Return the reservation data as JSON response
            return response()->json(['reservation' => $reservation]);
        } catch (Exception $e) {
            // Handle any unexpected errors
            Log::error('Error fetching reservation by ID: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch reservation', 'details' => $e->getMessage()], 500);
        }
    }

}
