<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $all_users = User::all();
        $users = [];

        foreach($all_users as $user) {
            $roles = json_decode($user->role);
            $user->role = $roles;
            if(!in_array('user', $roles) && !in_array('maintenance', $roles)) {
                array_push($users, $user);
            }
        }
        return response()->json(['users' => $users]);
    }

    public function show(int $personnel)
    {
        $user = User::findorfail($personnel);
        $user->role = json_decode($user->role);

        return $user;
    }

    public function store(Request $request)
    {
        $validator = Validator::make( $request->all(), [
            'username' => 'required|unique:users',
            // 'patron_id' => 'required',
            // 'department' => 'required',
            // 'position' => 'required',
            'password' => 'required',
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'ext_name' => 'nullable',
            'role' => 'required|string'
        ]);

        if($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'ext_name' => $request->ext_name
        ]);

        $log = new ActivityLogController();
        $logParam = new \stdClass(); // Instantiate stdClass

        $currentUser = $request->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Added user with username ' . $request->username;

        $log->savePersonnelLog($logParam);

        $user->role = json_decode($user->role);
        return response()->json(['success'=> $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'password' => 'nullable',
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'ext_name' => 'nullable',
            'role' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update([
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'ext_name' => $request->ext_name
        ]);

        // Logging
        $log = new ActivityLogController();
        $logParam = new \stdClass(); // Instantiate stdClass

        $currentUser = $request->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Updated user with username ' . $user->username;

        $log->savePersonnelLog($logParam);

        $user = $user->fresh();
        $user->role = json_decode($user->role);

        return response()->json([
            'success'=> 'User updated successfully',
            'data'=> $user
        ], 200);
    }

    public function destroy($id)
    {
        // Find the user by ID, including soft-deleted ones
        $user = User::withTrashed()->findOrFail($id);

        // Log the deletion activity
        $log = new ActivityLogController();
        $logParam = new \stdClass(); // Instantiate stdClass

        $currentUser = request()->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Deleted user with username ' . $user->username;

        $log->savePersonnelLog($logParam);

        // Soft delete the user (if not already deleted)
        if (!$user->trashed()) {
            $user->delete();
            $message = 'User deleted successfully';
        } else {
            $message = 'User was already deleted';
        }

        // Return a JSON response indicating success
        return response()->json([
            'message' => $message,
        ]);
    }
}
