<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LockersHistory;

class LockerHistoryController extends Controller
{
    public function saveLog($log)
    {
        
        file_put_contents(
            base_path('admin-lockers.log'), // Path to the log file
            date("Y-m-d H:i:s") . ';' . $log->username . ';' . $log->fullname . ';' . $log->position . ';' . $log->program . ';' . $log->desc . ';' . $log->device . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function getLockerHistory()
    {
        // Retrieve locker logs along with locker details sorted by created_at descending
        $logWithLockers = LockersHistory::with('locker')->orderBy('created_at', 'desc')->get();

        // Log the action
        $log = new \stdClass();
        $log->username = auth()->user()->username ?? 'guest';
        $log->fullname = auth()->user()->name ?? 'Guest User';
        $log->program = 'Get Locker History';
        $log->desc = 'Retrieved locker History along with locker details';
        $log->device = request()->header('User-Agent');

        $this->saveLog($log);

        return response()->json($logWithLockers, 200);
    }

    // In LockerController.php

    public function fetchLockersHistoryWithUsers(Request $request)
    {
        $filterBy = $request->input('filter_by');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $department = $request->input('department');
        $allPages = $request->input('all_pages', false);

        $query = LockersHistory::with('user.program')
            ->with('locker')
            ->orderBy('created_at', 'desc'); // Order by created_at descending

        if ($filterBy) {
            switch ($filterBy) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                default:
                    break;
            }
        }

        if ($fromDate && $toDate) {
            $fromDate = date('Y-m-d 00:00:00', strtotime($fromDate));
            $toDate = date('Y-m-d 23:59:59', strtotime($toDate));
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($department) {
            $query->whereHas('user.program', function ($subQuery) use ($department) {
                $subQuery->where('department_short', $department);
            });
        }

        if ($allPages) {
            // Fetch all filtered data without pagination
            $lockersHistoryWithUsers = $query->get();
        } else {
            // Paginate the data
            $perPage = $request->input('per_page', 10);
            $lockersHistoryWithUsers = $query->paginate($perPage);
        }

        // Log the action
        $user = auth()->user();
        $log = new \stdClass();
        $log->username = $user ? $user->username : 'admin';
        $log->fullname = $user ? $user->name : 'Admin';
        $log->position = $user ? $user->position : 'Unknown';
        $log->program = 'Fetch Locker History With Users';
        $log->desc = 'Fetched lockers History with users with filters';
        $log->device = $request->header('User-Agent');

        $this->saveLog($log);

        return response()->json($lockersHistoryWithUsers);
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
