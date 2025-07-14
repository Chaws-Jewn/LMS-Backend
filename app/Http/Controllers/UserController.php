<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $all_users = User::all();
        $users = [];

        foreach ($all_users as $user) {
            $roles = json_decode($user->role);
            $user->role = $roles;
            if (!in_array('user', $roles) && !in_array('maintenance', $roles)) {
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

    /**
     * user store function
     *
     * allows initial creation of password
     * returns the newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'role' => $request->role,
            'position' => $request->position ?? 'Staff',
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
        return response()->json(['success' => $user], 201);
    }

    /**
     * update user
     *
     * SHALL not be able to change password, fit to front end
     * also returns the new user data
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'position' => 'required',
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'ext_name' => 'nullable',
            'role' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update([
            'username' => $request->username,
            'position' => $request->position,
            'role' => $request->role,
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
            'success' => 'User updated successfully',
            'data' => $user
        ], 200);
    }

    public function destroy(Request $request)
    {
        $user = User::findOrFail($request->id);

        // Log the deletion activity
        $log = new ActivityLogController();
        $logParam = new \stdClass();

        $currentUser = request()->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Deleted user with username ' . $user->username;

        $log->savePersonnelLog($logParam);

        try {
            $user->delete();
            $message = 'User deleted successfully';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return response()->json([
            'message' => $message,
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['error' => 'Old password is incorrect'], 422);
        } else if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['error' => 'New password cannot be the same as the old password'], 422);
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

            return response()->json(['success' => 'Password changed successfully'], 200);
        }
    }
}
