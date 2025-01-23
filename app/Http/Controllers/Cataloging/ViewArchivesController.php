<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ViewArchivesController extends Controller
{
    // const URL = 'http://26.68.32.39:8000';
    const URL = 'http://127.0.0.1:8000';

    function getMaterials(string $type) {
        switch($type) {
            case 'books':
                $material_type = 0;
                break;
            
            case 'periodicals': 
                $material_type = 1;
                break;

            case 'articles':
                $material_type = 2;
                break;

            case 'audio-visuals':
                $material_type = 3;
                break;

            case 'projects':
                break;

            default:
                return response()->json(['message' => 'Material type not found'], 404);
        }

        if($type == 'projects') $materials = DB::connection('archives')->table('academic_projects')->orderByDesc('archived_at')->get(['archived_at', 'accession', 'title', 'authors']);
        else $materials = DB::connection('archives')->table('materials')->where('material_type', $material_type)->orderByDesc('archived_at')->get(['archived_at', 'accession', 'title', 'authors']);
        
        foreach($materials as $material) {
            $material->authors = json_decode($material->authors);
        }

        return $materials;
    }

    function getMaterialsByType(string $type, int $periodical_type) {
        try {
            switch($type) {
                case 'periodicals':
                    $material_type = 1;
                    break;

                case 'articles':
                    $material_type = 2;
                    break;

                default:
                    return response()->json(['message' => 'Invalid material type'], 404);
            }

            $materials = DB::connection('archives')->table('materials')->where('material_type', $material_type)
            ->where('periodical_type', $periodical_type)->orderByDesc('archived_at')
            ->get(['archived_at', 'accession', 'title', 'authors']);

            foreach($materials as $material) {
                $material->authors = json_decode($material->authors);
            }
            
            return $materials;
        } catch (Exception $e) {
            return response()->json(['message' => 'Invalid periodical type'], 404);
        }
    }

    function getMaterial(string $type, $id) {
        switch($type){
            case 'material':
                $material = DB::connection('archives')->table('materials')->where('accession', $id)->first();
                break;

            case 'project':
                $material = DB::connection('archives')->table('academic_projects')->where('accession', $id)->first();
                if($material->keywords) $material->keywords = json_decode($material->keywords);
                break;

            default:
                return response()->json(['message' => 'Invalid periodical type'], 404);
        }   

        if($material->authors) $material->authors = json_decode($material->authors);
        if($material->image_url) $material->image_url = config('app.url') . Storage::url($material->image_url);
        return $material;
    }
}
