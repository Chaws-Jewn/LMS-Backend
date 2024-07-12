<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollegeController extends Controller
{
    public function getDepartmentsWithPrograms()
    {   
        $departments = Program::select('program_short', 'program_full', 'department_full', 'department_short')
                        ->get()
                        ->groupBy('department_full');

        return $departments;
    }

    public function getDepartments() {
        $departments = Program::select('department_full', 'department_short')
                        ->groupBy(['department_full', 'department_short'])
                        ->get();

        return $departments;
    }
    // Add a new college
    public function addCollege(Request $request)
    {
        $data = Validator::make($request->all(), [
            'department' => 'required|string|max:10|unique:departments',
            'full_department' => 'required|string|max:255'
        ]);

        if($data->fails()) {
            return response()->json(['error' => $data->errors()], 422);
        }

        $department = Department::create($data->validated());

        return response()->json(['message' => 'College added successfully!', 'department' => $department], 201);
    }
}