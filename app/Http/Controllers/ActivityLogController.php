<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    // Required system, username, fullname, position, desc
    public function savePersonnelLog($log) {
        file_put_contents(storage_path('logs/personnelActivity.log'), date("Y-m-d H:i:s") . ';' . $log->system . ';' . $log->username . ';' . $log->fullname . ';' . $log->position . ';' . $log->desc . ';' . PHP_EOL, FILE_APPEND | LOCK_EX);
        // file_put_contents("personnelActivity.log", date("Y-m-d H:i:s") . ';' . $log->system . ';' . $log->username . ';' . $log->fullname . ';' . $log->position . ';' . $log->desc . ';' . PHP_EOL,FILE_APPEND | LOCK_EX);
    }

    // Required system, username, department, program, fullname, position, desc
    public function saveStudentLog($log) {
        file_put_contents("studentActivity.log", date("Y-m-d H:i:s") . ';' . $log->system . ';' . $log->username . ';' . $log->department . ';' . $log->program . ';' . $log->fullname . ';' . $log->position . ';' . $log->desc . ';' . PHP_EOL,FILE_APPEND | LOCK_EX);
    }

    // Tester for routes
    // public function savePersonnelLog(Request $request) {
    //     file_put_contents(".log", date("Y-m-d H:i:s") . ';' . $request->system . ';' . $request->username . ';' . $request->fullname . ';' . $request->position . ';' . $request->desc . ';' . PHP_EOL,FILE_APPEND | LOCK_EX);
    // }

    // default bigay ni sir
//   public saveLog($log) {
//     file_put_contents("filename.log", date("Y-m-d H:i:s") . ';' . $log->username . ';' . $log->fullname . ';' . $log->dept . ';' . $log->program . ';' . $log->desc . ';' . $log->device . PHP_EOL,FILE_APPEND | LOCK_EX);
// }
}
