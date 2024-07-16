<?php

namespace App\Http\Controllers\Cataloging;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Http\Controllers\ActivityLogController;

class MaterialArchiveController extends Controller
{
    public function store(Request $request, $id) {
        $model = Material::findOrFail($id);
        $accession = $model->accession;

        switch($model->material_type) {
            case 0:
                $type = 'book';
                break;

            case 1:
                $type = 'periodical';
                break;

            case 2:
                $type = 'article';
                break;

            case 3:
                $type = 'audio-visual';
                break;
        }
        
        DB::transaction(function () use ($model, $id) {

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
        
        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        if($type == 'Periodical' || $type == 'Article') {
            switch($model->periodical_type) {
                case 0:
                    $perioType = 'journal ';
                    break;
                
                case 1:
                    $perioType = 'magazine ';
                    break;
                
                case 2:
                    $perioType = 'newspaper ';
                    break;
            }
            
            $desc = 'Archived ' . $type . ' ' . $perioType . ' of accession ' . $accession;
        } else $desc = 'Archived ' . $type . ' of accession ' . $accession;
        

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = $desc;

        $log->savePersonnelLog($logParam);

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
