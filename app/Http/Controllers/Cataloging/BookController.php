<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Http\Controllers\ImageController;
use Exception;
use Illuminate\Http\Request;
use App\Models\Location;
use Storage, Str, DB;

class BookController extends Controller
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
            'copyright' => 'required|integer|min:1901|max:'.date('Y'),
            'volume' => 'nullable|string',
            'edition' => 'nullable|string',
            'acquired_date' => 'required|date',
            'source_of_fund' => 'required|string|max:30',
            'price' => 'nullable|numeric',
            'location' => 'required|string',
            'call_number' => 'required|string|max:50',
            'author_number' => 'required|string|max:50',
            'copies' => 'required|integer|min:1',
            'image_url' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if($request->copies < 1) {
            return response()->json(['Error' => 'Invalid number of copies'], 400);
        } else {
            for($i = 0; $i < $request->copies; $i++) {

                $model = new Material();
                try {
                    
                    $model->fill($request->except(['accession', 'image_url']));
                    $model->material_type = 0;
                    
                    // get id if request has an id
                    if($i > 0 && $request->accession) {

                        $model->accession = $request->accession + $i;
                    } else if($i == 0 && $request->accession) {

                        $model->accession = $request->accession;
                    }

                } catch (Exception) {
                    return response()->json(['Error' => 'Invalid form request. Check values if on correct data format.', 400]);
                }

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
                
                $authors = json_decode($request->authors, true);

                foreach($authors as &$author) {
                    $author = Str::title($author);
                }

                $model->authors = json_encode($authors);
                
                $model->save();
            }
        }


        // $location = Location::where('id', $model->location_id)->value('location');

        // $log = new CatalogingLogController();

        // if($request->copies == 1)
        //     $title = $model->title;
        // else
        //     $title = $model->title . ' (' . $request->copies . ')';

        // $log->add($request->user()->id, 'Added', $title, 'book', $location);
        
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
            'copyright' => 'required|integer|min:1901|max:'.date('Y'),
            'volume' => 'nullable|string',
            'edition' => 'nullable|string',
            'acquired_date' => 'required|date',
            'source_of_fund' => 'required|string|max:30',
            'price' => 'nullable|numeric',
            'location' => 'required|string',
            'call_number' => 'required|string|max:50',
            'author_number' => 'required|string|max:50',
            'image_url' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        $model = Material::where('accession', $id)->firstOrFail();

        try {
            $model->fill($request->except('image_url', 'title', 'authors'));
        } catch (Exception) {
            return response()->json(['Error' => 'Invalid form request. Check values if on correct data format.'], 400);
        }

        if(!empty($request->image_url)) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }

            $path = $request->file('image_url')->store('public/images/books');
            $model->update(['image_url' => $path]);
        }

        $model->title = Str::title($request->title);
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        $model->authors = json_encode($authors);
        $model->save();

        return response()->json($model, 200);
    }
}

