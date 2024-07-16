<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class StudentViewController extends Controller
{
    // const URL = 'http://26.68.32.39:8000';
    const URL = 'http://127.0.0.1:8000';
    // BOOKS
    public function viewBooks() {
        

          $books = Material::all(); // Fetching all books

    $books_array = [];
    foreach($books as $book) {
        $image_url = $book->image_url ? self::URL . Storage::url($book->image_url) : null;

        array_push($books_array, [
            'id' => $book->accession, 
            'image_url' => $image_url,
            ' location' => $book->location,
            'authors' => json_decode($book->authors),
            'volume' => $book->volume,
            'edition' => $book->edition,
            'available' => $book->status, 
            'copyright' => $book->copyright,
            'fine' => $book->fine ?? 0
        ]);
    }
    return $books_array;
}

    public function viewBook(int $id) {
        $book = Material::find($id, ['available', 'title', 'id', 'call_number', 'copyright', 'price', 'authors',
        'volume', 'pages', 'edition', 'remarks', 'image_url']);

        $book->authors = json_decode($book->authors);
        $book->image_url = self::URL . Storage::url($book->image_url);
        return $book;
    }

    // PERIODICALS
    public function viewPeriodicals() {
        $periodicals = Material::
        select(['id', 'title', 'authors', 'material_type', 'image_url', 'language', 'volume', 'issue', 'copyright', 'remarks'])
        ->orderByDesc('updated_at')->get();

        foreach($periodicals as $periodical) {
            if($periodical->image_url != null)
                $periodical->image_url = self::URL .  Storage::url($periodical->image_url);

            $periodical->authors = json_decode($periodical->authors);
        }
        
        return $periodicals;
    }

    public function getPeriodicalByMaterialType($materialType)
    {
        // Filter articles by material type
        $filteredPeriodical = Material::where('material_type', $materialType)->get();

        return response()->json($filteredPeriodical);
    }

    // ARTICLES
    public function viewArticles() {
        $articles = Material::
        select(['material_type', 'title', 'authors', 'language', 'subject', 'date_published', 
        'publisher', 'volume', 'issue', 'abstract'])
        ->orderByDesc('created_at')->get();
        
        foreach($articles as $article) {
            $article->authors = json_decode($article->authors);
        }
        
        return $articles;
    }

    public function viewArticle(int $id) {
        $article = Material::find($id, ['material_type', 'title', 'authors', 'language', 'subject', 'date_published', 
        'publisher', 'volume', 'issue', 'abstract']);
        
        $article->authors = json_decode($article->authors);
        
        return $article;
    }

    public function viewArticlesByType($type) {
        $articles = Material::where('material_type', $type)->orderByDesc('updated_at')->get();
        
        foreach($articles as $article) {
            $article->authors = json_decode($article->authors);
        }

        return $articles;
    }

    // PROJECTS
    public function getProjectCategoriesByDepartment($department) {
        // Define the mapping of department strings to department IDs
        $departmentMapping = [
            'CCS' => 1,
            'CAHS' => 2,
            'CEAS' => 3,
            'CHTM' => 4,
            'CBA' => 5,
            // Add other mappings as necessary
        ];
    
        // Check if the provided department string exists in the mapping
        if (!isset($departmentMapping[$department])) {
            return response()->json(['error' => 'Invalid department'], 400);
        }
    
        // Get the department ID from the mapping
        $departmentId = $departmentMapping[$department];
    
        // Retrieve projects with their related program
        $projects = Project::with('program')->get();
    
        // Filter projects based on the provided department
        $filteredProjects = $projects->filter(function ($project) use ($departmentId) {
            return $project->program->department_id == $departmentId;
        });
    
        // Group projects by category
        $groupedProjects = $filteredProjects->groupBy('category');
    
        // Get the category names
        $categories = $groupedProjects->keys();
    
        // Prepare the response array containing category names and their respective projects
        $projectCategories = [];
        foreach ($categories as $category) {
            $projectCategories[] = [
                'category' => $category,
                'projects' => $groupedProjects[$category],
            ];
        }
    
        // Return the response as JSON
        return response()->json($projectCategories);
    }

}
