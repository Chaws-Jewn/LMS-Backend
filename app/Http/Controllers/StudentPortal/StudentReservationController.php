<?php

namespace App\Http\Controllers\StudentPortal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BorrowMaterial;
use App\Models\User;
use App\Models\Material;
use App\Models\Patron;
use App\Models\Program;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class StudentReservationController extends Controller
{
    public function reservebook(Request $request)
    {
        $payload = $request->all();
        $logMessages = [];
        $logMessages[] = 'Received payload: ' . json_encode($payload);
        Log::info('Received payload:', $payload);
    
        // Check if the required fields are present
        $requiredFields = ['book_id', 'user_id', 'reserve_expiration'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                return response()->json(['error' => 'Missing required field: ' . $field, 'logMessages' => $logMessages], 400);
            }
        }
    
        return $payload;
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
            $activeReservationsCount = BorrowMaterial::where('user_id', $payload['user_id'])
                                                    ->where('status', 1) // 1 means active reservation
                                                    ->count();
    
            if ($activeReservationsCount >= $materialsAllowed) {
                return response()->json(['error' => 'User already has the maximum number of active reservations allowed'], 400);
            }
    
            // Use a transaction to ensure both operations happen at the same time
            DB::beginTransaction();
            try {
                // Create a new BorrowMaterial instance (reservation)
                $reservation = new BorrowMaterial();
                $reservation->book_id = $payload['book_id'];
                $reservation->user_id = $payload['user_id'];
                $reservation->reserve_date = now(); // Timestamp of reservation creation
                $reservation->reserve_expiration = $payload['reserve_expiration'];
                $reservation->fine = 0; // Initial fine set to 0 for reservation
                $reservation->reservation_type = 0; // Assuming 1 is walk-in
                $reservation->status = 1; // Status 1 for active reservation
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
}
