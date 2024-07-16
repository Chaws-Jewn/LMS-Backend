<?php

namespace App\Http\Controllers\Cataloging;

use App\Models\Material;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Storage, Str;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ActivityLogController;

class PeriodicalController extends Controller
{
    // const URL = 'http://26.68.32.39:8000'; 
    const URL = 'http://127.0.0.1:8000';

    public function add(Request $request) {
        
        $request->validate([
            'accession' => 'nullable|string|max:20',
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:512',
            'pages' => 'required|integer|min:1',
            'volume' => 'required|string|max:100',
            'issue' => 'required|string|max:100',
            'language' => 'required|string|max:15',
            'acquired_date' => 'required|date',
            'date_published' => 'required|date',
            'copyright' => 'required|integer|min:1900|max:'.date('Y'),
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $model = new Material();
        $model->material_type = 1;

        $model->fill($request->except('image_url', 'authors'));

        if(!empty($request->image_url)) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }

            /// Store image and save path
            if($request->image_url != null) {
                $ext = $request->file('image_url')->extension();

                // Check file extension and raise error
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
                }

                // Store image and save path
                $path = $request->file('image_url')->store('public/images/periodicals');

                $model->image_url = $path;
            } 
        }

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);
        
        $model->save();

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
        $logParam->desc = 'Added ' . $type . 'periodical of accession ' . $request->accession;

        $log->savePersonnelLog($logParam);



        return response()->json($model, 201);
    }

    public function update(Request $request, $id) {

        $request->validate([
            'accession' => 'nullable|string|max:20',
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:512',
            'pages' => 'required|integer|min:1',
            'volume' => 'required|string|max:100',
            'issue' => 'required|string|max:100',
            'language' => 'required|string|max:15',
            'acquired_date' => 'required|date',
            'date_published' => 'required|date',
            'copyright' => 'required|integer|min:1900|max:'.date('Y'),
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $model = Material::findOrFail($id);

        $model->fill($request->except('image_url'));

        if(!empty($request->image_url)) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }
             
            $path = $request->file('image_url')->store('public/images/periodicals');
            $model->image_url = $path;
        }

        $model->save();

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
        $logParam->desc = 'Updated ' . $type . 'periodical of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);
    }
}

