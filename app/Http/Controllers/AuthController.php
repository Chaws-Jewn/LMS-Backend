<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Exception;
use Auth;
use DB, Http, Str;
use PhpParser\JsonDecoder;
use Illuminate\Support\Facades\Validator;
use Storage;

class AuthController extends Controller
{
    public function studentLogin(Request $request)
    {

        // Get credentials from the request
        $credentials = $request->only('username', 'password');

        // Send credentials to the external authentication API
        $response = Http::post('http://127.0.0.1:8001/api/login', $credentials);

        // Check if the authentication was successful
        if ($response->successful()) {
            // Extract user data from the response
            $userData = $response->json();

            // Generate a random API token
            $apiToken = Str::random(80); // Adjust the length as needed

            // Hash the token before storing it
            $hashedToken = Hash::make($apiToken);

            $expiryTime = now()->addHour();
            $now = now();

            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => 'StudentUser',
                'name' => 'student-token',
                'tokenable_id' => null,
                'token' => $hashedToken,
                'expires_at' => $expiryTime,
                'updated_at' => $now,
                'created_at' => $now
            ]);

            // Return the token to the client
            return response()->json([
                'token' => $apiToken,
                'first_name' => $userData['details']['first_name'],
                'last_name' => $userData['details']['last_name'],
                'student_number' => $userData['details']['student_number']
            ], 200);
        } else {
            // Return error response if authentication failed
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function user(Request $request)
    {
        return $request->user();
    }

    public function login(Request $request, String $system)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {

            $user = Auth::user();
            $roles = json_decode($user->role);
            $abilities = [];

            // add ability if role is valids
            if (in_array($system, $roles))
                array_push($abilities, $system);

            // CREATE TOKENS WITH ABILITIES
            $token = $user->createToken(Str::title($system), $abilities)->plainTextToken;

            if (!in_array('student', $roles)) {
                $responseData = [
                    'token' => $token,
                    'id' => $user->id,
                    'displayName' => $user->first_name . ' ' . $user->last_name,
                    'position' => $user->position,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'domain_account' => $user->domain_email,
                    'main_address' => $user->main_address,
                ];

                if (in_array($system, $abilities)) {

                    $log = new ActivityLogController();

                    $logParam = new \stdClass(); // Instantiate stdClass

                    $user = $request->user();

                    $logParam->system = Str::title($system);
                    $logParam->username = $user->username;
                    $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
                    $logParam->position = $user->position;
                    $logParam->desc = 'Logged in GC-LMS ' . Str::title($system);

                    $log->savePersonnelLog($logParam);
                    return response()->json($responseData, 200);
                } else {
                    $log = new ActivityLogController();

                    $logParam = new \stdClass(); // Instantiate stdClass

                    $user = $request->user();

                    $logParam->system = Str::title($system);
                    $logParam->username = $user->username;
                    $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
                    $logParam->position = $user->position;
                    $logParam->desc = 'Attempted to log in GC-LMS ' . Str::title($system);

                    $log->savePersonnelLog($logParam);
                    return response()->json(['message' => 'Unauthorized'], 401);
                };
            } else if (in_array('student', $roles)) {
                $student = User::with('student_program')->find($user->id);

                $responseData = [
                    'token' => $token,
                    'id' => $user->id,
                    'department' => $student->student_program->department_short,
                    'program' => $student->program,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'domain_account' => $user->domain_email,
                    'main_address' => $user->main_address,
                    'profile_picture' => config('app.url') .  Storage::url($user->profile_image)
                ];

                $log = new ActivityLogController();

                $logParam = new \stdClass(); // Instantiate stdClass

                $logParam->system = 'Student Portal';
                $logParam->username = $student->username;
                $logParam->fullname = $student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name . ' ' . $student->ext_name;
                $logParam->program = $student->program;
                $logParam->department = $student->student_program->department_short;
                $logParam->desc = 'Logged in GC-LMS Student Portal';

                $log->saveStudentLog($logParam);
                return response()->json($responseData, 200);
            } else {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        if (in_array($user->role, ['superadmin', 'admin']))
            $token = $user->createToken('token-name', ['materials:edit', 'materials:read'])->plainTextToken;

        // sets expiry time
        $tokenModel = $user->tokens->last();
        $expiryTime = now()->addHour();
        $tokenModel->update(['expires_at' => $expiryTime]);

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            $system = $user->currentAccessToken()->name;
            $abilities = $user->currentAccessToken()->abilities;

            $user->currentAccessToken()->delete();

            $log = new ActivityLogController();

            $logParam = new \stdClass(); // Instantiate stdClass

            $logParam->username = $user->username;
            $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
            if (in_array('student', $abilities)) {
                $student = User::with('student_program')->find($user->id);
                $logParam->system = 'Student';
                $logParam->program = $student->program;
                $logParam->department = $student->student_program->department_short;
                $logParam->desc = 'Logged Out of GC-LMS Student Portal';
                $log->saveStudentLog($logParam);
            } else {
                $logParam->system = $system;
                $logParam->position = $user->position;
                $logParam->desc = 'Logged Out of GC-LMS ' . Str::title($system);
                $log->savePersonnelLog($logParam);
            }


            return response()->json(['Status' => 'Logged out successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['Error' => $e->getMessage()], 400);
        }
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['user' => $user], 200);
    }



    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid content'], 422);
        }

        $user = $request->user();
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['messsage' => 'Old password is incorrect'], 422);
        } else if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'New password cannot be the same as the old password'], 422);
        } else {
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Log the password change activity
            $log = new ActivityLogController();
            $logParam = new \stdClass();

            $logParam->system = 'Maintenance';
            $logParam->username = $user->username;
            $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
            $logParam->position = $user->position;
            $logParam->desc = 'Changed password for user with username ' . $user->username;

            $log->savePersonnelLog($logParam);

            return response()->json(['message' => 'Password changed successfully'], 200);
        }
    }
}
