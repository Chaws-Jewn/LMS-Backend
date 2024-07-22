<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    // Required system, username, fullname, position, desc
    public function savePersonnelLog($log) {
        file_put_contents(storage_path('logs/personnelActivity.log'), date("Y-m-d H:i:s") . ';' . $log->system . ';' . $log->username . ';' . $log->fullname . ';' . $log->position . ';' . $log->desc . ';' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    // Required system, username, department, program, fullname, position, desc
    public function saveStudentLog($log) {
        file_put_contents(storage_path('logs/studentActivity.log'), date("Y-m-d H:i:s") . ';' . $log->system . ';' . $log->department . ';' . $log->program . ';' . $log->fullname . ';' . $log->username . ';' . $log->desc . ';' . PHP_EOL,FILE_APPEND | LOCK_EX);
    }
}
