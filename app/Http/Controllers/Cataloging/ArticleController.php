<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use Exception, Str, Storage;

class ArticleController extends Controller
{
    // const URL = 'http://26.68.32.39:8000';
    const URL = 'http://127.0.0.1:8000';

    public function add(Request $request) {

        $request->validate([
            'accession' => 'required|string|max:255',
            'periodical_type' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'pages' => 'required|string|max:25',
            'abstract' => 'required|string|max: 4096',
            'volume' => 'nullable|string|max:50',
            'issue' => 'nullable|string|max:50',
            'language' => 'required|string|max:15',
            'subject' => 'required|string|max:255',
            'date_published' => 'required|date'
        ]);

        // return response()->json(['res' => 'nearly there'], 200);
        $model = new Material();
        $model->material_type = 2;

        $model->fill($request->all());

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);

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

        switch($model->periodical_type) {
            case 0:
                $type = 'journal ';
                break;
            
            case 1:
                $type = 'magazine ';
                break;
            
            case 2:
                $type = 'newspaper ';
                break;
            
            default:
                $type = '';
                break;
        }

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Added ' . $type . 'article of accession ' . $request->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);
    }

    public function update(Request $request, $id) {

        $request->validate([
            'accession' => 'required|string|max:255',
            'periodical_type' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'pages' => 'required|string|max:25',
            'abstract' => 'required|string|max: 4096',
            'volume' => 'nullable|string|max:50',
            'issue' => 'nullable|string|max:50',
            'language' => 'required|string|max:15',
            'subject' => 'required|string|max:255',
            'date_published' => 'required|date'
        ]);

        $model = Material::findOrFail($id);
        $model->update($request->all());

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }
        
        $model->authors = json_encode($authors);

        try {
            $model->save();
        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['message' => 'Duplicate accession entry detected.'], 409); // HTTP status code 409 for conflict
            } else {
                return response()->json(['message' => 'Cannot process request.'], 400); // HTTP status code 500 for internal server error
            }
        }

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        switch($model->periodical_type) {
            case 0:
                $type = 'journal ';
                break;
            
            case 1:
                $type = 'magazine ';
                break;
            
            case 2:
                $type = 'newspaper ';
                break;
            
            default:
                $type = '';
                break;
        }

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated ' . $type . 'article of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);
    }
}
