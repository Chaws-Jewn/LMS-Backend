<?php

namespace App\Http\Controllers\Cataloging;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Http\Controllers\ActivityLogController;
use App\Models\Project;
use Exception;

class MaterialArchiveController extends Controller
{
    public function storeMaterial(Request $request, $id) {
        $model = Material::findOrFail($id);
        if($model->status != 0) return response()->json(['message' => 'Record is currently unavailable'], 400);
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
        
        $transact = DB::transaction(function () use ($model, $id) {

            try {
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
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000) {
                    return 'Duplicate accession entry on archives table.'; // HTTP status code 409 for conflict
                } else {
                    return 'Cannot process request.'; // HTTP status code 500 for internal server error
                }
            }

            $model->delete();
            return 'success';
        });
        
        if($transact != 'success') return response()->json(['message' => $transact], 409);

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        if($type == 'periodical' || $type == 'article') {
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

    public function storeProject(Request $request, $id) {
        
        $model = Project::findOrFail($id);

        $transact = DB::transaction(function () use ($model, $id) {
            try {
                DB::connection('archives')->table('academic_projects')->insert([
                    'accession' => $model->accession,
                    'category' => $model->category,
                    'title' => $model->title,
                    'authors' => $model->authors,
                    'program' => $model->program,
                    'image_url' => $model->image_url,
                    'date_published' => $model->date_published,
                    'keywords' => $model->keywords,
                    'language' => $model->language,
                    'abstract' => $model->abstract,
                    'created_at' => $model->created_at,
                    'archived_at' => now()
                ]);
            }
            catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000) {
                    return 'Duplicate accession entry on archives table.'; // HTTP status code 409 for conflict
                } else {
                    return 'Cannot process request.'; // HTTP status code 500 for internal server error
                }
            }

            $model->delete();
            return 'success';
        });

        if($transact != 'success') return response()->json(['message' => $transact], 409);

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Archived project of accession ' . $id;

        $log->savePersonnelLog($logParam);

        return response()->json(['Response' => 'Record archived successfully'], 200);
    }


    public function restoreMaterial(Request $request, $id) {
        try {
            $delete = DB::connection('archives')->table('materials')->where('accession', $id);
            $model = $delete->first();

            switch($model->material_type) {
                case 0:
                    $material_type = 'book';
                    break;
    
                case 1:
                    $material_type = 'periodical';
                    break;
    
                case 2:
                    $material_type = 'article';
                    break;
                
                case 3: 
                    $material_type = 'audio -visual';
                    break;
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Cannot find material'], 404);
        }

        
        $transact = DB::transaction(function () use ($model, $delete) {
            try {
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
                    'updated_at' => now()
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000) {
                    return 'Duplicate accession entry on materials table.'; // HTTP status code 409 for conflict
                } else {
                    return 'Cannot process request.'; // HTTP status code 500 for internal server error
                }
            }

            $delete->delete();
            return 'success';
        });

        if($transact != 'success') return response()->json(['message' => $transact], 409);

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Restored archived ' . $material_type . ' of accession ' . $id;

        $log->savePersonnelLog($logParam);

        return response()->json(['Response' => 'Record restored successfully'], 200);
    }

    public function restoreProject(Request $request, $id) {
        
        $transact = DB::transaction(function () use ($id) {
            try {
                $delete = DB::connection('archives')->table('academic_projects')->where('accession', $id);
                $model = $delete->first();
            } catch (Exception $e) {
                return response()->json(['message' => 'Cannot find material'], 404);
            }

            try {
                Project::insert([
                    'accession' => $model->accession,
                    'category' => $model->category,
                    'title' => $model->title,
                    'authors' => $model->authors,
                    'program' => $model->program,
                    'image_url' => $model->image_url,
                    'date_published' => $model->date_published,
                    'keywords' => $model->keywords,
                    'language' => $model->language,
                    'abstract' => $model->abstract,
                    'created_at' => $model->created_at,
                    'updated_at' => now()
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000) {
                    return 'Duplicate accession entry on projects table.'; // HTTP status code 409 for conflict
                } else {
                    return 'Cannot process request.'; // HTTP status code 500 for internal server error
                }
            }

            return 'success';
            
            $delete->delete();
        });

        if($transact != 'success') return response()->json(['message' => $transact], 409);
        
        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Restored archived project of accession ' . $id;

        $log->savePersonnelLog($logParam);

        return response()->json(['Response' => 'Record restored successfully'], 200);
    }

    //PERMANENTLY DELETE
    public function deleteMaterial(Request $request, String $type, String $id) {
        try {
            switch($type) {
                case 'materials':
                    $model = DB::connection('archives')->table('materials')->where('accession', $id);
                    break;

                case 'projects':
                    $model = DB::connection('archives')->table('academic_projects')->where('accession', $id);
                    break;

                default:
                    return response()->json(['message' => 'Type does not exist'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Cannot find record'], 200);
        }
        
        try {
            if($type == 'materials') {
                switch($model->first()->material_type) {
                    case 0:
                        $material_type = 'book';
                        break;

                    case 1:
                        $material_type = 'periodical';
                        break;

                    case 2:
                        $material_type = 'article';
                        break;

                    case 3:
                        $material_type = 'audio-visual';
                        break;
                }
            } else {
                $material_type = 'project';
            }

            $accession = $model->first()->accession;
            $model->delete();
            
            $log = new ActivityLogController();

            $logParam = new \stdClass(); // Instantiate stdClass
    
            $user = $request->user();
    
            $logParam->system = 'Cataloging';
            $logParam->username = $user->username;
            $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
            $logParam->position = $user->position;
            $logParam->desc = 'Permanently deleted ' . $material_type . ' of accession ' . $accession;
    
            $log->savePersonnelLog($logParam);

            return response()->json(['message' => 'Record permanently deleted'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Cannot delete record.'], 400);
        }
    }
}
