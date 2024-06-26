<?php

namespace App\Http\Controllers\OPAC;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class OPACMaterialsController extends Controller
{
    // const URL = 'http://26.68.32.39:8000';
    const URL = 'http://127.0.0.1:8000';

    public function getBooks() {        
        $books = Material::select('accession', 'title', 'date_published', 'authors', 'image_url')
                    ->where('material_type', 0)
                    ->orderBy('date_published', 'desc')
                    ->get();

        foreach ($books as $book) {
            $book->authors = json_decode($book->authors);
            if ($book->image_url != null) {
                $book->image_url = self::URL . Storage::url($book->image_url);
            }
        }
        
        return $books;
    }


    public function getPeriodicals($periodical_type){
        if (!in_array($periodical_type, ['0', '1', '2'])) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $periodicals = Material::select('accession', 'title', 'date_published', 'authors', 'image_url')
                                    ->where('material_type', 1)
                                    ->where('periodical_type', $periodical_type)
                                    ->orderBy('date_published', 'desc')
                                    ->get();
        
        foreach($periodicals as $periodical) {
            if($periodical->image_url != null)
                $periodical->image_url = self::URL .  Storage::url($periodical->image_url);
            
            $periodical->authors = json_decode($periodical->authors);
        }

        return $periodicals;
    }

    public function getArticles(){
        $articles = Material::select('accession', 'title', 'date_published', 'authors', 'abstract')
                            ->where('material_type', 2)
                            ->orderBy('date_published', 'desc')
                            ->get();
                             
        foreach($articles as $article) {
            $article->authors = json_decode($article->authors);
        }

        return $articles;
    }

    public function getMaterial($id) {
        $material = Material::findOrFail($id);

        $material->authors = json_decode($material->authors);
        if($material->image_url != null)
            $material->image_url = self::URL . Storage::url($material->image_url);

        return $material;
    }


    public function getProjects($category){
        if(!in_array($category, ['thesis', 'Classroom Based Action Research', 'capstone', 'feasibility study', 'research', 'dissertation'])){
            return response()->json(['error' => 'Page not found'], 404);
        }

        $projects = Project::select('accession', 'title', 'image_url', 'date_published', 'authors', 'program', 'keywords')
                    ->where('category', $category)
                    ->with('project_program:program_short,department_short')
                    ->orderbyDesc('date_published')
                    ->get();

        foreach ($projects as $project) {
            $project->authors = json_decode($project->authors);
            $project->keywords = json_decode($project->keywords);
            if ($project->image_url != null) {
                $project->image_url = self::URL .  Storage::url($project->image_url);
            }
        }

        return $projects;       

    }

    public function getProject($id){
        $project = Project::select('accession', 'title', 'authors', 'program', 'image_url', 'language', 'keywords', 'abstract', 'date_published')
                        ->with('project_program')
                        ->findOrfail($id);

        $project->authors = json_decode($project->authors);
        $project->keywords = json_decode($project->keywords);

        if ($project->image_url != null) {
            $project->image_url = self::URL .  Storage::url($project->image_url);
        }
        return $project;
    }
}