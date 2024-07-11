<?php

namespace App\Http\Controllers\Cataloging;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Material;

class MaterialArchiveController extends Controller
{
    public function store(Request $request, $id) {
        DB::transaction(function () use ($id) {
            $model = Material::findOrFail($id);

            DB::connection('archives')->table('materials')->insert([
                'accession' => $model->accession,
                'material_type' => $model->material_type,
                'title' => $model->title,
                'authors' => $model->authors,
                'publisher' => $model->publisher,
                'image_url' => $model->image_url,
                'location' => $model->location,
                'volume' => $model->volume,
                'edition' => $model->edition,
                'pages' => $model->pages,
                'acquired_date' => $model->acquired_date,
                'date_published' => $model->date_published,
                'remarks' => $model->remarks,
                'copyright' => $model->copyright,
                'call_number' => $model->call_number,
                'source_of_fund' => $model->source_of_fund,
                'price' => $model->price,
                'status' => $model->status,
                'inventory_status' => $model->inventory_status,
                'periodical_type' => $model->periodical_type,
                'language' => $model->language,
                'issue' => $model->issue,
                'subject' => $model->subject,
                'abstract' => $model->abstract,
                'created_at' => $model->created_at,
                'archived_at' => now()
            ]);
            
            $model->delete();
        });

        return response()->json(['Response' => 'Record archived successfully'], 200);
    }

    public function revert(Request $request, $id) {
        DB::transaction(function () use ($id) {
            $model = Material::findOrFail($id);

            DB::connection('archives')->delete('DELETE FROM materials WHERE accession = ?', [$id]);

            Material::insert([
                'accession' => $model->accession,
                'material_type' => $model->material_type,
                'title' => $model->title,
                'authors' => $model->authors,
                'publisher' => $model->publisher,
                'image_url' => $model->image_url,
                'location' => $model->location,
                'volume' => $model->volume,
                'edition' => $model->edition,
                'pages' => $model->pages,
                'acquired_date' => $model->acquired_date,
                'date_published' => $model->date_published,
                'remarks' => $model->remarks,
                'copyright' => $model->copyright,
                'call_number' => $model->call_number,
                'source_of_fund' => $model->source_of_fund,
                'price' => $model->price,
                'status' => $model->status,
                'inventory_status' => $model->inventory_status,
                'periodical_type' => $model->periodical_type,
                'language' => $model->language,
                'issue' => $model->issue,
                'subject' => $model->subject,
                'abstract' => $model->abstract,
                'created_at' => $model->created_at,
                'archived_at' => now()
            ]);
        });

        return response()->json(['Response' => 'Record archived successfully'], 200);
    }
}
