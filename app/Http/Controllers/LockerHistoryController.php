<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LockersHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LockerHistoryController extends Controller
{

    public function getLockerHistory()
    {
        // Retrieve locker logs along with locker details sorted by created_at descending
        $logWithLockers = LockersHistory::with('locker')->orderBy('created_at', 'desc')->get();

        return response()->json($logWithLockers, 200);
    }

    // In LockerController.php

    public function fetchLockersHistoryWithUsers(Request $request)
    {
        $filterBy = $request->input('filter_by');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $query = LockersHistory::with('user.program')
            ->with('locker')
            ->orderBy('created_at', 'desc');

        if ($fromDate && $toDate) {
            $fromDate = Carbon::parse($fromDate)->startOfDay();
            $toDate = Carbon::parse($toDate)->endOfDay();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($filterBy) {
            // Assuming locker_number is stored in the locker relationship
            $query->whereHas('locker', function ($q) use ($filterBy) {
                $q->where('locker_number', 'like', "%$filterBy%");
            });
        }

        $lockersHistoryWithUsers = $query->paginate($perPage);
        $total = $lockersHistoryWithUsers->total();

        return response()->json([
            'data' => $lockersHistoryWithUsers->items(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ]);
    }

    public function getLockersWithFilter(Request $request)
    {
        $filterBy = $request->input('filter_by');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Initialize the query with the necessary relationships
        $query = LockersHistory::with('user.program')
            ->with('locker')
            ->orderBy('created_at', 'desc');

        // Apply locker number filter if provided
        if ($filterBy) {
            $query->whereHas('locker', function ($q) use ($filterBy) {
                $q->where('locker_number', 'like', "%{$filterBy}%");
            });
        }

        // Apply date range filter if both dates are provided
        if ($fromDate && $toDate) {
            $fromDate = Carbon::parse($fromDate)->startOfDay();
            $toDate = Carbon::parse($toDate)->endOfDay();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        // Paginate the results
        $lockersHistoryWithUsers = $query->paginate($perPage);
        $total = $lockersHistoryWithUsers->total();

        // Return the response in JSON format
        return response()->json([
            'data' => $lockersHistoryWithUsers->items(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ]);
    }

    public function search(Request $request)
    {
        // Get search parameters from the request
        $searchQuery = $request->input('q'); // Search query
        $lockerNumber = $request->input('locker_number'); // Filter by locker number
        $department = $request->input('department'); // Filter by department
        $perPage = $request->input('per_page', 10); // Number of items per page, default to 10

        // Initialize the query builder for the LockersHistory model
        $queryBuilder = LockersHistory::with(['user', 'locker']); // Ensure relationships are defined

        // Apply search filters
        if ($searchQuery) {
            $queryBuilder->where(function ($q) use ($searchQuery) {
                $q->whereHas('user', function ($subQuery) use ($searchQuery) {
                    $subQuery->where('first_name', 'LIKE', "%$searchQuery%")
                        ->orWhere('last_name', 'LIKE', "%$searchQuery%")
                        ->orWhere('domain_email', 'LIKE', "%$searchQuery%");
                })
                    ->orWhereHas('locker', function ($subQuery) use ($searchQuery) {
                        $subQuery->where('locker_number', 'LIKE', "%$searchQuery%");
                    });
            });
        }

        if ($lockerNumber) {
            $queryBuilder->whereHas('locker', function ($subQuery) use ($lockerNumber) {
                $subQuery->where('locker_number', 'LIKE', "%$lockerNumber%");
            });
        }

        if ($department) {
            $queryBuilder->whereHas('user', function ($subQuery) use ($department) {
                $subQuery->where('program', $department);
            });
        }

        // Fetch paginated results
        $results = $queryBuilder->paginate($perPage);

        // Return results as JSON
        return response()->json($results);
    }


    public function getSearchData(Request $request)
    {
        $searchQuery = $request->input('search_query');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $query = LockersHistory::with(['user.program', 'locker'])  // Ensure locker relationship is loaded
            ->join('users', 'lockers_history.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program', '=', 'programs.program_short')
            ->leftJoin('lockers', 'lockers_history.locker_id', '=', 'lockers.id')
            ->select(
                'lockers_history.*',
                'users.first_name',
                'users.last_name',
                'lockers.locker_number',  // This should be included
                'programs.program_short',
                'programs.program_full'
            )
            ->orderBy('lockers_history.created_at', 'desc');


        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('users.first_name', 'like', "%{$searchQuery}%")
                    ->orWhere('users.last_name', 'like', "%{$searchQuery}%")
                    ->orWhere('programs.program_short', 'like', "%{$searchQuery}%")
                    ->orWhere('programs.program_full', 'like', "%{$searchQuery}%")
                    ->orWhere('lockers.locker_number', 'like', "%{$searchQuery}%");  // Search by locker_number
            });
        }

        if ($fromDate && $toDate) {
            $fromDate = Carbon::parse($fromDate)->startOfDay();
            $toDate = Carbon::parse($toDate)->endOfDay();
            $query->whereBetween('lockers_history.created_at', [$fromDate, $toDate]);
        }

        $lockersHistoryWithUsers = $query->paginate($perPage);
        $total = $lockersHistoryWithUsers->total();

        return response()->json([
            'data' => $lockersHistoryWithUsers->items(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ]);
    }


    public function getalldepartment(Request $request)
    {
        // Retrieve input parameters
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $department = $request->input('department'); // Department filter
        $lockerNumber = $request->input('locker_number'); // Locker number filter

        // Build the query
        $query = LockersHistory::with(['user.program', 'locker']) // Ensure 'locker' relationship is loaded
            ->join('users', 'lockers_history.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program', '=', 'programs.program_short')
            ->leftJoin('lockers', 'lockers_history.locker_id', '=', 'lockers.id')
            ->select(
                'lockers_history.*',
                'users.first_name',
                'users.last_name',
                'lockers.locker_number',
                'programs.program_short',
                'programs.program_full'
            )
            ->orderBy('lockers_history.created_at', 'desc');

        // Apply department filter if provided
        if ($department) {
            $query->whereHas('user.program', function ($q) use ($department) {
                $q->where('department_short', $department);
            });
        }

        // Apply locker number filter if provided
        if ($lockerNumber) {
            $query->whereHas('locker', function ($q) use ($lockerNumber) {
                $q->where('locker_number', $lockerNumber);
            });
        }

        // Paginate the results
        $lockersHistoryWithUsers = $query->paginate($perPage);

        // Return the paginated data as JSON
        return response()->json([
            'data' => $lockersHistoryWithUsers->items(),
            'total' => $lockersHistoryWithUsers->total(),
            'page' => $lockersHistoryWithUsers->currentPage(),
            'per_page' => $perPage
        ]);
    }

    public function add(int $id, string $action, string $log)
    {
        $model = LockersHistory::create([
            'user_id' => $id,
            'action' => $action,
            'log' => $log
        ]);

        $model->save();
    }
}
