<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use App\Http\Controllers\ActivityLogController;
use Str;

class AVController extends Controller
{
    public function add(Request $request) {
        $request->validate([
            'accession' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:255',
            'call_number' => 'required|string|max:50',
            'copyright' => 'required|integer|min:1901|max:'.date('Y'),
        ]);

        $model = new Material();
        $model->material_type = 3;

        $model->fill($request->except(['title', 'authors']));

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);
        $model->save();
        
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
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:255',
            'call_number' => 'required|string|max:50',
            'copyright' => 'required|integer|min:1901|max:'.date('Y'),
        ]);

        $model = Material::findOrFail($accession);

        $model->fill($request->all());

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);
        $model->save();

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
