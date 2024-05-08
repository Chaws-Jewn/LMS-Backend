<?php

use App\Models\Book;
use Illuminate\Support\Facades\Route;

// default view
Route::get('/', function (Request $request) {
    return response()->json(['Response' => 'Library Information Management System back end is up and running']);
});

Route::middleware(['auth', 'check.access:Circulation'])->group(function () {
    // Circulation related routes here
});

Route::resource('personnel', PersonnelController::class);

Route::resource('patron-types', 'PatronTypeController');

Route::resource('announcements', 'AnnouncementController');

Route::post('/inventory/clear', 'InventoryController@clear')->middleware('auth');

Route::get('/inventory/scan', 'InventoryController@showScanForm');
Route::post('/inventory/scan', 'InventoryController@processScanForm');

