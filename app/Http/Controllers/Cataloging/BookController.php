<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Http\Controllers\ImageController;
use Exception;
use Illuminate\Http\Request;
use App\Models\Location;
use Storage, Str, DB;
use App\Http\Controllers\ActivityLogController;

class BookController extends Controller
{

    public function add(Request $request)
    {
        $accessions = [];
        $request->validate([
            'accession' => 'required|string|max:20',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:512',
            'pages' => 'required|integer|min:1',
            'copyright' => 'nullable|string',
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

        if ($request->copies < 1) {
            return response()->json(['Error' => 'Invalid number of copies'], 400);
        } else {
            for ($i = 0; $i < $request->copies; $i++) {

                $model = new Material();
                try {

                    $model->fill($request->except(['accession', 'image_url', 'title']));
                    $model->material_type = 0;

                    // get id if request has an id
                    if ($i > 0 && $request->accession) {

                        $model->accession = $request->accession + $i;
                    } else if ($i == 0 && $request->accession) {

                        $model->accession = $request->accession;
                    }

                    array_push($accessions, $model->accession);
                } catch (Exception) {
                    return response()->json(['Error' => 'Invalid form request. Check values if on correct data format.', 400]);
                }

                if ($request->image_url != null) {
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

                foreach ($authors as &$author) {
                    $author = Str::title($author);
                }

                $model->title = Str::title($request->title);
                $model->authors = json_encode($authors);
                $model->status = 0;

                try {
                    $model->save();
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000) {
                        return response()->json(['message' => 'Duplicate accession entry detected.'], 409); // HTTP status code 409 for conflict
                    } else {
                        return response()->json(['message' => 'Cannot process request.'], 400); // HTTP status code 500 for internal server error
                    }
                }
            }
        }

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;

        if (count($accessions) == 1) $logParam->desc = 'Added book of accession ' . $request->accession;
        else $logParam->desc = 'Added books of accessions ' . $accessions[0] . ' - ' . $accessions[count($accessions) -  1];

        $log->savePersonnelLog($logParam);

        return response()->json($model, 201);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'accession' => 'required|string|max:20',
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:512',
            'pages' => 'required|integer|min:1',
            'copyright' => 'nullable|string',
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

        if (!empty($request->image_url)) {
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

        foreach ($authors as &$author) {
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

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated book of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);
    }
}
