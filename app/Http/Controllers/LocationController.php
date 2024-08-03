<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function getLocations() {
        $locations = Location::orderBy('created_at', 'desc')->get();

        return $locations;
    }

    public function create(Request $request) {
        $location = Validator::make($request->all(), [
            'location_short' => 'required|string|max:10|unique:locations',
            'location_full' => 'required|string|max:32'
        ]);

        if($location->fails()) {
            return response()->json(['error' => $location->errors()], 422);
        }

        $location = Location::create($location->validated());

        $log = new ActivityLogController();
        $logParam = new \stdClass();
        $currentUser = $request->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Created location with short name ' . $location->location_short;
        $log->savePersonnelLog($logParam);

        return response()->json(['success' => $location], 201);
    }
    public function update(Request $request, $location_short)
    {
        $location = Location::findOrFail($location_short);

        $validator = Validator::make($request->all(), [
            'location_short' => 'required|string|max:10|unique:locations,location_short,' . $location->location_short . ',location_short',
            'location_full' => 'required|string|max:32'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $location->update($validator->validated());
        $log = new ActivityLogController();
        $logParam = new \stdClass();
        $currentUser = $request->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Updated location with short name ' . $location->location_short;
        $log->savePersonnelLog($logParam);

        return response()->json(['success' => $location], 200);
    }


    public function destroy($location_short)
    {
        $location = Location::findOrFail($location_short);
        // Check for related records (e.g., books that reference the location)
        $relatedRecordsCount = Material::where('location', $location_short)->count();

        if ($relatedRecordsCount > 0) {
            return response()->json(['error' => 'Cannot delete location. There are related records associated with it.'], 422);
        }
        $location->delete();

        $log = new ActivityLogController();
        $logParam = new \stdClass();
        $currentUser = auth()->user();
        $logParam->system = 'Maintenance';
        $logParam->username = $currentUser->username;
        $logParam->fullname = $currentUser->first_name . ' ' . $currentUser->middle_name . ' ' . $currentUser->last_name . ' ' . $currentUser->ext_name;
        $logParam->position = $currentUser->position;
        $logParam->desc = 'Deleted location with short name ' . $location->location_short;
        $log->savePersonnelLog($logParam);

        return response()->json(['success' => 'Location has been deleted.'], 200);
    }
}
