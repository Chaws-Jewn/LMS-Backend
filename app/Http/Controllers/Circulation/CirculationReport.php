<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BorrowMaterial;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Material;
use Exception, Carbon, Storage;


class CirculationReport extends Controller
{
    public function report(Request $request)
    {
        try {
            // Retrieve filters from the request
            $departmentFilter = $request->input('department', null);
            $programFilter = $request->input('program', null);
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);
    
            // Build the query with filters
            $query = BorrowMaterial::with(['user.student_program'])
                ->whereNotNull('borrow_date');
    
            // Apply department filter
            if ($departmentFilter) {
                $query->whereHas('user.student_program', function ($query) use ($departmentFilter) {
                    $query->where('department_short', $departmentFilter);
                });
            }
    
            // Apply program filter
            if ($programFilter) {
                $query->whereHas('user.student_program', function ($query) use ($programFilter) {
                    $query->where('program_short', $programFilter);
                });
            }
    
            // Apply date range filter
            if ($dateFrom) {
                $query->whereDate('borrow_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('borrow_date', '<=', $dateTo);
            }
    
            $borrowMaterials = $query->get();
    
            // Initialize arrays to store counts
            $departmentCount = [];
            $programsCount = [];
            $genderCount = [
                'Male' => 0,
                'Female' => 0,
            ];
    
            // Process BorrowMaterials to count programs and genders
            foreach ($borrowMaterials as $borrowMaterial) {
                $programShort = $borrowMaterial->user->student_program->program_short;
                $departmentShort = $borrowMaterial->user->student_program->department_short;
                $gender = $borrowMaterial->user->gender == 1 ? 'Male' : 'Female';
    
                // Count programs
                if (!isset($programsCount[$programShort])) {
                    $programsCount[$programShort] = 0;
                }
                $programsCount[$programShort]++;
    
                // Count departments
                if (!isset($departmentCount[$departmentShort])) {
                    $departmentCount[$departmentShort] = 0;
                }
                $departmentCount[$departmentShort]++;
    
                // Count genders
                $genderCount[$gender]++;
            }
    
            // Construct final report data
            $reportData = [
                'programsCount' => $programsCount,
                'genderCount' => $genderCount,
                'departmentCount' => $departmentCount,
            ];
    
            // Return the processed data as JSON or in any desired format
            return response()->json($reportData);
        } catch (\Exception $e) {
            // Handle any exceptions, e.g., log the error
            return response()->json(['error' => 'An error occurred while fetching the report data: ' . $e->getMessage()], 500);
        }
    }
}
