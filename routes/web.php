<?php

use Illuminate\Support\Facades\Route;

// default view
Route::get('/', function (Request $request) {
    return response()->json(['Response' => 'Either you are testing the routes or your request has an error.']);
});