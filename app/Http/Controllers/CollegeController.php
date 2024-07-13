<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollegeController extends Controller
{
    public function getDepartments() {
        $departmentsWithPrograms = Program::select('program_short', 'program_full', 'department_full', 'department_short')
                        ->get()
                        ->groupBy('department_full');

        $departments = Program::select('department_full', 'department_short')
                        ->groupBy(['department_full', 'department_short'])
                        ->get();

        return response()->json(['departments_only' => $departments, 'departments_with_programs' => $departmentsWithPrograms], 200);
    }
    // Add a new college
    public function addDepartment(Request $request)
    {
        $data = Validator::make($request->all(), [
            'department_short' => 'required|string|max:10|unique:programs',
            'department_full' => 'required|string|max:100',
            'programs' => 'required|array',
            'programs.*.program_short' => 'required|string|max:10|unique:programs',
            'programs.*.program_full' => 'required|string|max:100',
            'programs.*.category' => 'required|string|max:32'
        ]);

        if($data->fails()) {
            return response()->json(['error' => $data->errors()], 422);
        }

        $validatedData = $data->validated();

        foreach($validatedData['programs'] as $program) {
            Program::create([
                'program_short' => $program['program_short'],
                'program_full' => $program['program_full'],
                'category' => $program['category'],
                'department_short' => $validatedData['department_short'],
                'department_full' => $validatedData['department_full'],
            ]);

        }

        return response()->json(['success' => 'College added successfully!'], 201);
    }
}