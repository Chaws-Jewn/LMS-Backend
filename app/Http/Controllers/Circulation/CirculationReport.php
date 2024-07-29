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
    //report || book borrowers by department and gender
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
    //------------------------------------------------------------------------------------------


    //report || Top Borrowers of Books
    public function topborrowers(Request $request)
    {
        try {
            // Retrieve filters from the request
            $departmentFilter = $request->input('department', null);
            $programFilter = $request->input('program', null);
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);
    
            // Build the query with filters
            $query = BorrowMaterial::with(['user.student_program' , 'user.patron'])
                ->select('user_id', DB::raw('COUNT(*) as borrow_count'))
                ->whereNotNull('borrow_date')
                ->groupBy('user_id')
                ->orderByDesc('borrow_count');
    
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
    
            // Execute the query and get the top borrowers
            $topBorrowers = $query->get();
    
            // Map to add user details to the result
            $topBorrowers = $topBorrowers->map(function($borrowMaterial) {
                return [
                    'user_id' => $borrowMaterial->user_id,
                    'borrow_count' => $borrowMaterial->borrow_count,
                    'last_name' => $borrowMaterial->user->last_name,
                    'first_name' => $borrowMaterial->user->first_name,
                    'patron' => $borrowMaterial->user->patron->patron,
                    'department' => $borrowMaterial->user->student_program->department_short,
                    'program' => $borrowMaterial->user->student_program->program_short,
                ];
            });
    
            return response()->json($topBorrowers, 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => 'An error occurred while fetching the top borrowers data: ' . $e->getMessage()], 500);
        }
    }
    //------------------------------------------------------------------------------------------
    
    
    //report || Most Borrowed Books
    public function mostBorrowed(Request $request)
    {
        try {
            // Retrieve filters from the request
            $departmentFilter = $request->input('department', null);
            $programFilter = $request->input('program', null);
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);

            // Build the query with filters
            $query = BorrowMaterial::select('book_id', DB::raw('COUNT(*) as borrow_count'))
                ->with('material:title,accession,location,publisher,date_published')
                ->groupBy('book_id')
                ->orderByDesc('borrow_count');

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

            // Limit the results to the top 10 most borrowed books
            $mostBorrowedBooks = $query->limit(10)->get();

            // Transform the data
            $formattedBooks = $mostBorrowedBooks->map(function($book) {
                return [
                    'book_id' => $book->book_id,
                    'borrow_count' => $book->borrow_count,
                    'title' => $book->material->title ?? 'No title available',
                    'location' => $book->material->location ?? 'No location available',
                    'publisher' => $book->material->publisher ?? 'No publisher available',
                    'date_published' => $book->material->date_published ?? 'No date available',
                ];
            });

            return response()->json($formattedBooks);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => 'An error occurred while fetching the most borrowed books data: ' . $e->getMessage()], 500);
        }
    }
}
