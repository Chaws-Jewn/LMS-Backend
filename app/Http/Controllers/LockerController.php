<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Models\User;
use DateTime;
use App\Models\LockersHistory;


class LockerController extends Controller
{

    private function saveLog($logDetails)
    {
        $position = property_exists($logDetails, 'position') ? $logDetails->position : 'Unknown';
        $fullname = property_exists($logDetails, 'fullname') ? $logDetails->fullname : 'Unknown';
        $studentNumber = property_exists($logDetails, 'studentNumber') ? $logDetails->studentNumber : 'Unknown';

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated book of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        $logEntry = "{$logDetails->username};{$fullname};{$studentNumber};{$position};{$logDetails->program};{$logDetails->desc};{$logDetails->device}";

        Log::info($logEntry);

        file_put_contents(
            base_path('admin-lockers.log'), // Path to the log file
            date("Y-m-d H:i:s") . ';' . $logDetails->username . ';' . $fullname . ';' . $studentNumber . ';' . $position . ';' . $logDetails->program . ';' . $logDetails->desc . ';' . $logDetails->device . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }



    public function getAllLockers()
    {
        $lockers = Locker::with('user:id,first_name,last_name,program,gender')
            ->select('Id', 'user_id', 'locker_number', 'status', 'remarks')
            ->get();

        return response()->json($lockers);
    }


    public function getLockerInfo($lockerId)
    {
        // Fetch the locker with nested user, program, and department relationships
        $locker = Locker::with(['user' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'program', 'gender')
                ->with(['program' => function ($programQuery) {
                    $programQuery->select('program_short', 'program_full', 'department_short', 'department_full');
                }]);
        }])->find($lockerId);

        // Check if locker is found
        if (!$locker) {
            return response()->json(['error' => 'Locker not found'], 404);
        }

        // Return the locker info in JSON format
        return response()->json($locker);
    }


    public function index()
    {
        $lockers = Locker::select('id', 'status', 'locker_number', 'updated_at', 'remarks', 'user_id')
                        ->with('user:id,first_name,last_name')
                        ->get();
        return $lockers;
    }

    public function store(Request $request)
    {
        // Validate the request
        $data = Validator::make($request->all(), [
            'numberOfLockers' => 'required|numeric|gt:0'
        ]);

        if ($data->fails()) {
            return response()->json(['errors' => $data->errors()], 400);
        }

        // Get the latest locker
        $latestLocker = Locker::latest('id')->first();
        $latestLockerNumber = $latestLocker ? intval($latestLocker->locker_number) : 0;

        /**
         * @var int $latestLockerNumber
         */

        $lockers = [];
        $user = $request->user();

        // Create new lockers
        for ($i = $latestLockerNumber + 1; $i <= $latestLockerNumber + $request->numberOfLockers; $i++) {
            $lockerNumber = str_pad($i, 3, '0', STR_PAD_LEFT);

            $locker = new Locker();
            $locker->locker_number = $lockerNumber;
            $locker->status = 'Available';
            $locker->save();

            $lockers[] = $locker;
        }

        // Log the action
        $logDetails = (object) [
            'username' => $user->username,
            'first_name' => $user->first_name,
            'position' => $user->position ?? 'Unknown',
            'program' => $user->program,
            'desc' => "Added {$request->numberOfLockers} new lockers starting from #" . ($latestLockerNumber + 1),
            'device' => $request->header('User-Agent')
        ];
        $this->saveLog($logDetails);

        return response()->json(['success' => $lockers]);
    }

    public function getStartingLockerNumber()
    {
        $latestLocker = Locker::latest('id')->first();
        $latestLockerNumber = $latestLocker ? intval($latestLocker->locker_number) : 0;
        return $latestLockerNumber + 1;
    }

    public function show($id)
    {
        $locker = Locker::select('id', 'locker_number', 'remarks', 'status')->findOrFail($id);
        return $locker;
    }

    public function update(Request $request, $id)
    {
        $data = Validator::make($request->all(), [
            'status' => 'required|in:Occupied,Available,Unavailable',
            'remarks' => 'nullable|string|max:256'
        ]);

        if ($data->fails()) {
            return response()->json(['errors' => $data->errors()], 400);
        }

        $locker = Locker::findOrFail($id);
        $validatedData = $data->validated();

        if ($validatedData['status'] === 'Available') {
            $validatedData['remarks'] = null;
        }

        $locker->update($validatedData);

        $user = $request->user();

        // Log the action
        $logDetails = (object) [
            'username' => $user->username,
            'first_name' => $user->first_name,
            'position' => $user->position ?? 'Unknown',
            'program' => $user->program,
            'desc' => "Updated locker #{$locker->locker_number}",
            'device' => $request->header('User-Agent')
        ];
        $this->saveLog($logDetails);

        return response()->json(['success' => $locker]);
    }

    public function destroy($id)
    {
//        $latestLocker = Locker::latest('id')->first();
//
//        if ($latestLocker && $latestLocker->id != $id) {
//            return response()->json(['errors' => 'You must delete the latest locker first.'], 400);
//        }

        $locker = Locker::findOrFail($id);

        // Delete related records from lockers_logs
        LockersHistory::where('locker_id', $id)->delete();

        // Set locker number in case it gets deleted
        $lockerNumber = $locker->locker_number;

        $locker->delete();

        // Log the action
        $user = auth()->user();
        $logDetails = (object) [
            'username' => $user->username,
            'first_name' => $user->first_name,
            'position' => $user->position ?? 'Unknown',
            'program' => $user->program,
            'desc' => "Deleted locker #{$lockerNumber}",
            'device' => request()->header('User-Agent')
        ];
        $this->saveLog($logDetails);

        return response()->json(['success' => 'Locker has been deleted.']);
    }





    //LOCKER MAINTENANCE
    public function locker(Request $request)
    {
        $request->validate([
            'locker_number' => 'required|unique:lockers',
            'status' => 'required',
        ]);

        $locker = new Locker();
        $locker->locker_number = $request->input('locker_number');
        $locker->status = $request->input('status');
        $locker->save();

        return response()->json(['message' => 'Locker added successfully'], 201);
    }


    public function scanLocker(Request $request, $lockerId)
    {
        try {
            $scannedData = $request->input('scannedData');
            $userId = null;

            if ($scannedData && strpos($scannedData, 'StudentNumber:') === 0) {
                $parts = explode(':', $scannedData);
                $userId = $parts[1];
            } else {
                $locker = Locker::find($lockerId);

                if (!$locker) {
                    return response()->json(['error' => 'Locker not found'], 404);
                }

                if ($locker->status === 'Occupied') {
                    $locker->status = 'Available';
                    $locker->user_id = null;

                    $log = LockersHistory::where('locker_id', $locker->id)->whereNull('time_out')->first();
                    if ($log) {
                        $log->update(['time_out' => Carbon::now()]);
                    }

                    $freedByName = auth()->user() ? auth()->user()->first_name . ' ' . auth()->user()->last_name : 'Front Desk';

                    $logDetails = (object) [
                        'username' => auth()->user()->username ?? 'unknown',
                        'fullname' => $freedByName,
                        'studentNumber' => '',
                        'position' => 'Front Desk',
                        'program' => '',
                        'desc' => "freed locker #{$locker->locker_number}",
                        'device' => 'server'
                    ];
                    $this->saveLog($logDetails);

                    $locker->save();
                    return response()->json($locker);
                } else {
                    return response()->json(['error' => 'Locker not occupied'], 400);
                }
            }

            if (!$userId) {
                return response()->json(['error' => 'Invalid scanned data: User ID missing'], 400);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $locker = Locker::find($lockerId);

            if (!$locker) {
                return response()->json(['error' => 'Locker not found'], 404);
            }

            $occupiedLocker = Locker::where('user_id', $user->id)->where('status', 'Occupied')->first();
            if ($occupiedLocker && $occupiedLocker->id != $locker->id) {
                // Log out from currently occupied locker
                $occupiedLocker->status = 'Available';
                $occupiedLocker->user_id = null;

                $log = LockersHistory::where('locker_id', $occupiedLocker->id)->whereNull('time_out')->first();
                if ($log) {
                    $log->update(['time_out' => Carbon::now()]);
                }

                $occupiedLocker->save();

                // Log log out event
                $logDetails = (object) [
                    'username' => $user->username,
                    'fullname' => $user->first_name . ' ' . $user->last_name,
                    'studentNumber' => $user->id,
                    'position' => $user->position ?? 'Unknown',
                    'program' => $user->program ?? '',
                    'desc' => "freed locker #{$occupiedLocker->locker_number}",
                    'device' => 'server'
                ];
                $this->saveLog($logDetails);
            }

            if ($locker->status === 'Occupied') {
                if ($user->id !== $locker->user_id) {
                    return response()->json(['error' => 'User ID doesn\'t match'], 400);
                }

                $locker->status = 'Available';
                $locker->user_id = null;

                $log = LockersHistory::where('locker_id', $locker->id)->whereNull('time_out')->first();
                if ($log) {
                    $log->update(['time_out' => Carbon::now()]);
                }

                $logDetails = (object) [
                    'username' => $user->username,
                    'fullname' => $user->first_name . ' ' . $user->last_name,
                    'studentNumber' => $user->id,
                    'position' => $user->position ?? 'Unknown',
                    'program' => $user->program ?? '',
                    'desc' => "freed locker #{$locker->locker_number}",
                    'device' => 'server'
                ];

                $this->saveLog($logDetails);
            } else {
                $locker->status = 'Occupied';
                $locker->user_id = $user->id;

                LockersHistory::create([
                    'locker_id' => $locker->id,
                    'user_id' => $user->id,
                    'time_in' => Carbon::now(),
                ]);

                $logDetails = (object) [
                    'username' => $user->username,
                    'fullname' => $user->first_name . ' ' . $user->last_name,
                    'studentNumber' => $user->id,
                    'position' => $user->position ?? 'Unknown',
                    'program' => $user->program ?? '',
                    'desc' => "occupied locker #{$locker->locker_number}",
                    'device' => 'server'
                ];

                $this->saveLog($logDetails);
            }

            $locker->save();
            return response()->json($locker);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }



    public function scanLockerQRCode(Request $request, $lockerId)
    {
        try {
            $scannedData = $request->input('scannedData');
            $userId = null;

            if ($scannedData && strpos($scannedData, 'StudentNumber:') === 0) {
                $parts = explode(':', $scannedData);
                $userId = $parts[1];
            } else {
                $locker = Locker::find($lockerId);

                if (!$locker) {
                    Log::error('Locker not found: ' . $lockerId);
                    return response()->json(['error' => 'Locker not found'], 404);
                }

                if ($locker->status === 'Occupied') {
                    $locker->status = 'Available';
                    $locker->user_id = null;

                    $log = LockersHistory::where('locker_id', $locker->id)->whereNull('time_out')->first();
                    if ($log) {
                        $log->update(['time_out' => Carbon::now()]);
                    }

                    $locker->save();
                    Log::debug('Updated locker: ' . json_encode($locker));

                    // Log the action
                    $logDetails = (object) [
                        'username' => auth()->user()->username,
                        'fullname' => 'Front Desk', // Use a default value for the fullname
                        'studentNumber' => '', // Empty string for studentNumber as it's not available for the front desk
                        'position' => 'Front Desk',
                        'program' => '',
                        'desc' => "freed locker #{$locker->locker_number}",
                        'device' => 'server'
                    ];
                    $this->saveLog($logDetails);

                    return response()->json($locker);
                } else {
                    Log::error('Locker not occupied: ' . $lockerId);
                    return response()->json(['error' => 'Locker not occupied'], 400);
                }
            }

            Log::debug('Scanned data: ' . $scannedData);
            Log::debug('User ID: ' . $userId);

            $user = User::find($userId);

            if (!$user) {
                Log::error('User not found with ID: ' . $userId);
                return response()->json(['error' => 'User not found'], 404);
            }

            $locker = Locker::find($lockerId);

            if (!$locker) {
                Log::error('Locker not found: ' . $lockerId);
                return response()->json(['error' => 'Locker not found'], 404);
            }

            Log::debug('Locker: ' . json_encode($locker));

            // Check if the user is already occupying another locker
            $occupiedLocker = Locker::where('user_id', $user->id)->where('status', 'Occupied')->first();
            if ($occupiedLocker && $occupiedLocker->id != $locker->id) {
                Log::error('User ID: ' . $userId . ' is already occupying locker: ' . $occupiedLocker->id);
                return response()->json(['error' => 'User is already occupying another locker', 'occupiedLocker' => $occupiedLocker->locker_number], 400);
            }

            if ($locker->status === 'Occupied') {
                if ($user->id !== $locker->user_id) {
                    Log::error('Invalid user ID: ' . $userId . ' for locker: ' . $lockerId);
                    return response()->json(['error' => 'StudentNumber doesn\'t match for this locker'], 400);
                }
                // Update the locker status only if the StudentNumber matches the user_id holding the locker
                $locker->status = 'Available';
                $locker->user_id = null;

                $log = LockersHistory::where('user_id', $user->id)->whereNull('time_out')->first();
                if ($log) {
                    $log->update(['time_out' => Carbon::now()]);
                }

                // Determine the position
                $position = ($user) ? $user->position ?? 'Unknown' : 'Front Desk';

                // Log the action
                $logDetails = (object) [
                    'username' => auth()->user()->username,
                    'fullname' => ($user) ? $user->first_name . ' ' . $user->last_name : 'Front Desk',
                    'studentNumber' => ($user) ? $user->id : '', // Dapat siguruhing mag-check ng pagiging NULL o hindi ng $user bago mag-access ng mga properties nito
                    'position' => $position,
                    'program' => ($user) ? $user->program : '',
                    'desc' => "freed locker #{$locker->locker_number}",
                    'device' => 'server'
                ];

                $this->saveLog($logDetails);
            } else {
                // Determine the position
                $position = ($user) ? $user->position ?? 'Unknown' : 'Front Desk';

                $locker->status = 'Occupied';
                $locker->user_id = $user->id;

                LockersHistory::create([
                    'locker_id' => $locker->id,
                    'user_id' => $user->id,
                    'time_in' => Carbon::now(),
                ]);

                // Log the action
                $logDetails = (object) [
                    'username' => auth()->user()->username,
                    'fullname' => ($user) ? $user->first_name . ' ' . $user->last_name : 'Front Desk',
                    'studentNumber' => ($user) ? $user->id : '', // Dapat siguruhing mag-check ng pagiging NULL o hindi ng $user bago mag-access ng mga properties nito
                    'position' => $position,
                    'program' => ($user) ? $user->program : '',
                    'desc' => "occupied locker #{$locker->locker_number}",
                    'device' => 'server'
                ];

                $this->saveLog($logDetails);
            }

            $locker->save();
            Log::debug('Updated locker: ' . json_encode($locker));
            return response()->json($locker);
        } catch (\Exception $e) {
            Log::error('Error scanning QR code: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }


    public function getLockerCounts()
    {
        $available = Locker::where('status', 'available')->count();
        $occupied = Locker::where('status', 'occupied')->count();
        $unavailable = Locker::where('status', 'unavailable')->count();
        $total = Locker::count();

        $totalUsers = LockersHistory::distinct()->count('id');

        // Add filtering by days, weeks, and months for total users
        $todayUsers = LockersHistory::whereDate('created_at', today())->distinct()->count('id');
        $thisWeekUsers = LockersHistory::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->distinct()->count('id');
        $thisMonthUsers = LockersHistory::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->distinct()->count('id');

        $counts = [
            'available' => $available,
            'occupied' => $occupied,
            'unavailable' => $unavailable,
            'total' => $total,
            'totalUsers' => $totalUsers,
            'todayUsers' => $todayUsers,
            'thisWeekUsers' => $thisWeekUsers,
            'thisMonthUsers' => $thisMonthUsers,
        ];

        return response()->json($counts);
    }

    // Other methods as needed

    public function getLockersWithUsers()
    {
        $lockersWithUsers = Locker::with('user')->get();

        return response()->json($lockersWithUsers);
    }

    public function getGenderCounts(Request $request)
    {
        $period = $request->query('period');

        $query = LockersHistory::selectRaw('count(*) as count, users.gender')
            ->join('users', 'users.id', '=', 'lockers_history.user_id')
            ->groupBy('users.gender');

        if ($period === 'custom') {
            $startDate = $request->query('from_date');
            $endDate = $request->query('to_date');

            if ($startDate && $endDate) {
                // Adjust end date to include the entire day
                $endDate = date('Y-m-d 23:59:59', strtotime($endDate));

                $query->whereBetween('lockers_history.created_at', [$startDate, $endDate]);
            } else {
                return response()->json(['error' => 'Start date and end date are required for custom filtering.'], 400);
            }
        } elseif ($period !== 'all') {
            switch ($period) {
                case 'today':
                    $query->whereDate('lockers_history.created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('lockers_history.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('lockers_history.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                default:
                    return response()->json(['error' => 'Invalid period parameter.'], 400);
            }
        }

        // Debugging output
        $sqlQuery = $query->toSql();
        $bindings = $query->getBindings();
        Log::info('SQL Query: ' . $sqlQuery, $bindings);

        $results = $query->get();
        $maleCount = 0;
        $femaleCount = 0;

        foreach ($results as $result) {
            if ($result->gender == 1) {
                $maleCount += $result->count;
            } elseif ($result->gender == 0) {
                $femaleCount += $result->count;
            }
        }

        $genderCounts = [
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
        ];

        // Debugging output
        Log::info('Gender Counts:', $genderCounts);

        return response()->json($genderCounts);
    }



    public function getDashboardGenderCounts(Request $request)
    {
        $period = $request->query('period');

        $query = LockersHistory::selectRaw('count(*) as count, users.gender')
            ->join('users', 'users.id', '=', 'lockers_history.user_id')
            ->groupBy('users.gender');

        $startDate = null;
        $endDate = null;

        // Get results without any date filtering first
        $results = $query->get();

        switch ($period) {
            case 'today':
                $startDate = new DateTime();
                $startDate->setTime(0, 0, 0);
                $endDate = new DateTime();
                $endDate->setTime(23, 59, 59);
                break;
            case 'week':
                $startDate = new DateTime('monday this week'); // Start of current week
                $endDate = new DateTime('sunday this week'); // End of current week
                $endDate->setTime(23, 59, 59);
                break;
            case 'month':
                $startDate = new DateTime();
                $startDate->modify('first day of this month');
                $endDate = new DateTime();
                $endDate->modify('last day of this month');
                $endDate->setTime(23, 59, 59);
                break;
            case 'all':
                // No need to set start and end date for 'all'
                break;
            default:
                // Handle invalid or missing period parameter
                return response()->json(['error' => 'Invalid period parameter.']);
        }

        // Apply date filtering if start and end dates are set
        if ($startDate && $endDate) {
            $query->whereBetween('lockers_history.created_at', [$startDate, $endDate]);
        }

        // Fetch results with applied date filtering
        $results = $query->get();

        $maleCount = 0;
        $femaleCount = 0;

        foreach ($results as $result) {
            // Check if gender is 1 (male) or 0 (female)
            if ($result->gender == 1) {
                $maleCount += $result->count;
            } elseif ($result->gender == 0) {
                $femaleCount += $result->count;
            }
        }

        $genderCounts = [
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
        ];

        return response()->json($genderCounts);
    }


    public function getCollegeCounts(Request $request)
    {
        $filter = $request->input('period'); // get the filter type (days, weeks, months)
        $fromDate = $request->input('from_date'); // get the custom from date
        $toDate = $request->input('to_date'); // get the custom to date

        $dateRange = null;
        if ($fromDate && $toDate) {
            // Validate custom date range
            if (strtotime($fromDate) === false || strtotime($toDate) === false) {
                return response()->json(['error' => 'Invalid date format.'], 400);
            }
            $dateRange = [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))];
        } elseif ($filter) {
            switch ($filter) {
                case 'days':
                    $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
                    break;
                case 'weeks':
                    $dateRange = [Carbon::today()->startOfWeek(), Carbon::today()->endOfWeek()];
                    break;
                case 'months':
                    $dateRange = [Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth()];
                    break;
                default:
                    $dateRange = null;
            }
        }

        // Department counts
        $departments = ['CEAS', 'CHTM', 'CBA', 'CAHS', 'CCS'];
        $collegeCounts = [];

        foreach ($departments as $department) {
            // Count of records in lockers_history for each department
            $departmentCount = LockersHistory::whereHas('user.program', function ($query) use ($department, $dateRange) {
                $query->where('department_short', $department);
                if ($dateRange) {
                    $query->whereBetween('created_at', $dateRange);
                }
            })->count();

            // Program counts for each department
            $programCounts = LockersHistory::whereHas('user.program', function ($query) use ($department, $dateRange) {
                $query->where('department_short', $department);
                if ($dateRange) {
                    $query->whereBetween('created_at', $dateRange);
                }
            })
                ->join('users', 'lockers_history.user_id', '=', 'users.id')
                ->join('programs', 'users.program', '=', 'programs.program_short')
                ->where('programs.department_short', $department)
                ->groupBy('programs.program_short')
                ->selectRaw('programs.program_short, count(*) as total')
                ->pluck('total', 'programs.program_short')
                ->toArray();

            // Log the SQL query
            $sql = LockersHistory::whereHas('user.program', function ($query) use ($department, $dateRange) {
                $query->where('department_short', $department);
                if ($dateRange) {
                    $query->whereBetween('created_at', $dateRange);
                }
            })->toSql();
            Log::info("SQL Query for $department:", [$sql]);

            // Store department count and program counts
            $collegeCounts[$department] = [
                'departmentCount' => $departmentCount,
                'programCounts' => $programCounts,
            ];
        }

        // Department counts

        $ceasCount = LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
            $query->where('department_short', 'CEAS');
            if ($dateRange) {
                $query->whereBetween('lockers_history.created_at', $dateRange);
            }
        })->count();

        $chtmCount = LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
            $query->where('department_short', 'CHTM');
            if ($dateRange) {
                $query->whereBetween('lockers_history.created_at', $dateRange);
            }
        })->count();


        $cbaCount = LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
            $query->where('department_short', 'CBA');
            if ($dateRange) {
                $query->whereBetween('lockers_history.created_at', $dateRange);
            }
        })->count();

        $cahsCount = LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
            $query->where('department_short', 'CAHS');
            if ($dateRange) {
                $query->whereBetween('lockers_history.created_at', $dateRange);
            }
        })->count();

        $ccsCount = LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
            $query->where('department_short', 'CCS');
            if ($dateRange) {
                $query->whereBetween('lockers_history.created_at', $dateRange);
            }
        })->count();




        // Program counts for CEAS department
        $ceasProgramCounts = [
            'BACOMM' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BACOMM');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BCAED' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BCAED');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BECED' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BECED');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BEED' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BEED');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BPED' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BPED');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDBIO' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSED-BIO');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDENG' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSED-ENG');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDFIL' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSED-FIL');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDMATH' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSED-MATH');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDMAPEH' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSED-MAPEH');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDSCI' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSED-SCI');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDSOC' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSED-SOC');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSEDPROFED' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSED-PROFED');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),
        ];


        // Program counts for CHTM department
        $chtmProgramCounts = [
            // 'BSHM' =>LockersHistory::where('collegeDepartment', 'CHTM')->where('collegeProgram', 'BSHM')->count(),
            'BSHM' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSHM');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            // 'BSHRM' =>LockersHistory::where('collegeDepartment', 'CHTM')->where('collegeProgram', 'BSHRM')->count(),
            'BSHRM' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSHRM');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            // 'BSTM' =>LockersHistory::where('collegeDepartment', 'CHTM')->where('collegeProgram', 'BSTM')->count(),
            'BSTM' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSTM');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),
        ];

        // Program counts for CBA department
        $cbaProgramCounts = [
            'BSA' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSA');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSBAFM' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSBA-FM');

                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSBAHRM' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSBA-HRM');

                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSBAMKT' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSBA-MKT');

                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSCA' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSCA');

                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),
        ];

        // Program counts for CAHS department
        $cahsProgramCounts = [
            'BSM' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSM');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSN' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSN');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),
        ];

        // Program counts for CCS department
        $ccsProgramCounts = [
            // 'BSCS' =>LockersHistory::where('collegeDepartment', 'CCS')->where('collegeProgram', 'BSCS')->count(),
            'BSCS' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSCS');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            'BSIT' => LockersHistory::whereHas('user.program', function ($query) use ($dateRange) {
                $query->where('program', 'BSIT');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),


            // 'BSEMC' =>LockersHistory::where('collegeDepartment', 'CCS')->where('collegeProgram', 'BSEMC')->count(),
            'BSEMC' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'BSEMC');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

            // 'ACT' => LockersHistory::where('collegeDepartment', 'CCS')->where('collegeProgram', 'ACT')->count(),
            'ACT' => LockersHistory::whereHas('user.program', function ($query)  use ($dateRange) {
                $query->where('program', 'ACT');
                if ($dateRange) {
                    $query->whereBetween('lockers_history.created_at', $dateRange);
                }
            })->count(),

        ];


        // Prepare the response array
        $collegeCounts = [
            'CEAS' => [
                'departmentCount' => $ceasCount,
                'programCounts' => $ceasProgramCounts,
            ],
            'CHTM' => [
                'departmentCount' => $chtmCount,
                'programCounts' => $chtmProgramCounts,
            ],
            'CBA' => [
                'departmentCount' => $cbaCount,
                'programCounts' => $cbaProgramCounts,
            ],
            'CAHS' => [
                'departmentCount' => $cahsCount,
                'programCounts' => $cahsProgramCounts,
            ],
            'CCS' => [
                'departmentCount' => $ccsCount,
                'programCounts' => $ccsProgramCounts,
            ],
        ];

        // Return the response as JSON
        return response()->json($collegeCounts);
    }
}
