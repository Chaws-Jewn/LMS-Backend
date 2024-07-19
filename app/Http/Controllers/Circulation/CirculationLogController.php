<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CirculationLogController extends Controller
{
    public function savePersonnelLog($logParam)
    {
        $logMessage = '[' . now() . '] ' .
            $logParam->system . ' | ' .
            $logParam->username . ' (' .
            $logParam->position . ') | ' .
            $logParam->fullname . ' | ' .
            $logParam->desc;

        Log::channel('circulation')->info($logMessage);
    }
}
