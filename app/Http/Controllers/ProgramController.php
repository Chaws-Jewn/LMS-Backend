<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramController extends Controller
{
    public function get(Request $request) {
        return Program::all();
    }

    public function addProgram(Request $request){
        $data = Validator::make($request->all(), [
            'program_short' => 'required|string|max:10',
            'program_full' => 'required|string|max:100',
            'category' => 'required|string|max:32',
            'department_short' => 'required|string|max:32',
            'department_full' => 'required|string|max:64'
        ]);

        if($data->fails()) {
            return response()->json(['errors', $data->errors()], 422);
        }

        $program = Program::create($data->validated());

        // Log the activity
        $log = new ActivityLogController();
        $logParam = new \stdClass();
        $user = $request->user();
        $logParam->system = 'Program Management';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Added a new program: ' . $program->program_full;
        $log->savePersonnelLog($logParam);

        return response()->json(['success' => 'Program has been created.'], 201);
    }

    public function viewDepartmentProgram($id)
    {
       $department = Department::with('programs')->findorfail($id);

       return $department;
    }
}
