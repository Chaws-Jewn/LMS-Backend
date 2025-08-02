<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Http\Controllers\ImageController;
use Exception;
use Illuminate\Http\Request;
use App\Models\Location;
use Str, DB;
use Illuminate\Support\Facades\Storage;

class MaterialViewController extends Controller
{
    public function getMaterials(String $type, bool $isReport = false)
    {
        $materials = [];
        switch ($type) {
            case 'books':
                if ($isReport) {
                    $materialsRaw = Material::where('material_type', $type)
                        ->get(['accession', 'title', 'authors', 'location', 'copyright', 'acquired_date', 'created_at']);

                    $materials = $materialsRaw->map(function ($material) {
                        $material->authors = json_decode($material->authors);
                        return $material;
                    })->sortBy(function ($material) {
                        return (int) $material->accession;
                    })->values();
                } else {
                    $materialsRaw = Material::where('material_type', $type)->orderByDesc('updated_at')
                        ->get(['accession', 'title', 'authors', 'location', 'copyright', 'acquired_date', 'created_at']);

                    $materials = $materialsRaw->map(function ($material) {
                        $material->authors = json_decode($material->authors);
                        return $material;
                    })->values();
                }
                break;

            case 'periodicals':
                $materials = Material::where([['material_type', 1], ['periodical_type', 0]])
                    ->orderByDesc('updated_at')
                    ->get(['accession', 'title', 'authors', 'publisher', 'copyright', 'created_at']);
                break;

            case 'articles':
                $materials = Material::where([['material_type', 2]])
                    ->orderByDesc('updated_at')
                    ->get(['accession', 'title', 'authors', 'publisher', 'date_published', 'created_at']);
                break;

            case 'audio-visuals':
                $materials = Material::where([['material_type', 3]])
                    ->orderByDesc('updated_at')
                    ->get(['accession', 'title', 'authors', 'copyright', 'created_at']);
                break;

            default:
                return response()->json(['response' => 'Invalid material type']);

                foreach ($materials as $material) {
                    if ($material->authors)
                        $material->authors = json_decode($material->authors);
                }
        }

        if ($type != 'books') {

            foreach ($materials as $material) {
                $material->authors = json_decode($material->authors);
            }
        }

        return $materials;
    }

    public function getMaterialsByType(String $type, String $periodical_type)
    {
        $columns = [];
        switch ($type) {
            case 'periodicals':
                $material_type = 1;
                $columns = ['accession', 'title', 'authors', 'publisher', 'copyright', 'acquired_date', 'created_at'];
                break;

            case 'articles':
                $material_type = 2;
                $columns = ['accession', 'title', 'authors', 'publisher', 'date_published', 'copyright', 'created_at'];
                break;

            default:
                return response()->json(['response' => 'Invalid material type']);
        }

        $materials = Material::where([['material_type', $material_type], ['periodical_type', $periodical_type]])
            ->orderByDesc('updated_at')
            ->get($columns);

        foreach ($materials as $material) {
            $material->authors = json_decode($material->authors);
        }

        return $materials;
    }

    public function getMaterial($id)
    {
        $material = Material::where('accession', $id)->firstOrFail();
        if ($material->authors) $material->authors = json_decode($material->authors);
        if ($material->keywords) $material->keywords = json_decode($material->keywords);
        if ($material->image_url) $material->image_url = config('app.url') .  Storage::url($material->image_url);
        return $material;
    }
}
