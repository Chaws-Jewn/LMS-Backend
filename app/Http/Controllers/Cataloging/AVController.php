<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Str, Illuminate\Support\Facades\Storage;

class AVController extends Controller
{
    public function add(Request $request) {
        $request->validate([
            'accession' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'call_number' => 'required|string|max:50',
            'copyright' => 'nullable|string',
            'image_url' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        $model = new Material();
        $model->material_type = 3;

        $model->fill($request->except(['title', 'authors', 'image_url']));

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);

        if($request->image_url != null) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }

            // Store image and save path
            $path = $request->file('image_url')->store('public/images/books');

            $model->image_url = $path;
        }
        
        try {
            $model->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['message' => 'Duplicate accession entry detected.'], 409); // HTTP status code 409 for conflict
            } else {
                return response()->json(['message' => 'Cannot process request.'], 400); // HTTP status code 500 for internal server error
            }
        }
        
        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Added audio visual of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);

    }

    public function update(Request $request, string $accession) {
        $request->validate([
            'accession' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'call_number' => 'required|string|max:50',
            'copyright' => 'nullable|string',
            'image_url' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        $model = Material::findOrFail($accession);

        $model->fill($request->except(['title', 'authors', 'image_url']));

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);

        if($request->image_url != null) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }

            // Store image and save path
            $path = $request->file('image_url')->store('public/images/books');

            $model->image_url = $path;
        }
        
        try {
            $model->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['message' => 'Duplicate accession entry detected.'], 409); // HTTP status code 409 for conflict
            } else {
                return response()->json(['message' => 'Cannot process request.'], 400); // HTTP status code 500 for internal server error
            }
        }

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated audio visual of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);

    }
}
