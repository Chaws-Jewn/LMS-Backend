<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Material;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class StudentMaterialController extends Controller
{
    const URL = 'http://26.68.32.39:8000';
    // const URL = 'http://192.168.243.174:8000';

    // View periodicals for student portal
    public function getPeriodicals() {
        $periodicals = Material::where('material_type', 1)
            ->select(['accession', 'title', 'authors', 'image_url', 'language', 'volume', 'issue', 'copyright', 'remarks', 'location'])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($periodicals as $periodical) {
            $this->processImageURL($periodical);
            $this->decodeAuthors($periodical);
        }

        return response()->json($periodicals);
    }

    // Get periodicals by type
    public function getByType($type) {
        $periodicals = Material::where('material_type', $type)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($periodicals as $periodical) {
            $this->processImageURL($periodical);
            $this->decodeAuthors($periodical);
        }

        return response()->json($periodicals);
    }

    // Get a specific periodical
    public function getPeriodical($accession) {
        $periodical = Material::where('material_type', 1)
            ->where('accession', $accession)
            ->firstOrFail();

            $this->decodeAuthors($periodical);
            $this->processImageURL($periodical);
        
        return response()->json($periodical);
    }

    // Get periodical by periodical type
    public function getPeriodicalByPeriodicalType($periodicalType) {
        $typeMapping = [
            'journal' => 0,
            'magazine' => 1,
            'newspaper' => 2
        ];

        if (!array_key_exists($periodicalType, $typeMapping)) {
            return response()->json(['message' => 'Invalid periodical type.'], 400);
        }

        $typeValue = $typeMapping[$periodicalType];

        $filteredPeriodicals = Material::where('periodical_type', $typeValue)
            ->where('material_type', 1)
            ->get();

        foreach ($filteredPeriodicals as $periodical) {
            $this->processImageURL($periodical);
            $this->decodeAuthors($periodical);
        }

        return response()->json($filteredPeriodicals);
    }

    // Search periodicals
    public function searchPeriodicals(Request $request) {
        $query = $request->input('query');
    
        if (empty($query)) {
            return response()->json(['message' => 'Please provide a search query.'], 400);
        }
    
        // Normalize the query for case-insensitive search
        $query = '%' . strtolower($query) . '%';
    
        $periodicals = Material::where(function($queryBuilder) use ($query) {
            $queryBuilder->where('title', 'like', $query)
                         ->orWhere('authors', 'like', $query);
        })
        ->where('material_type', 1)
        ->get();
    
        foreach ($periodicals as $periodical) {
            $this->processImageURL($periodical);
            $this->decodeAuthors($periodical);
        }
    
        return response()->json($periodicals);
    }

    // View articles for student portal
    public function viewArticles() {
        $articles = Material::where('material_type', 2)
            ->select(['accession', 'title', 'authors', 'language', 'subject', 'date_published', 'publisher', 'volume', 'issue', 'abstract'])
            ->orderByDesc('created_at')
            ->get();

        foreach ($articles as $article) {
            $this->decodeAuthors($article);
        }

        return response()->json($articles);
    }

    // Get a specific article
 public function viewArticle($accession): JsonResponse
    {
        $article = Material::where('material_type', 2)
            ->where('accession', $accession)
            ->firstOrFail();

        $this->decodeAuthors($article);

        return response()->json($article);
    }

   
    // View articles by type
    public function viewArticlesByType($type) {
        $typeMapping = [
            'journal' => 0,
            'magazine' => 1,
            'newspaper' => 2
        ];

        if (!array_key_exists($type, $typeMapping)) {
            return response()->json(['message' => 'Invalid article type.'], 400);
        }

        $typeValue = $typeMapping[$type];

        $articles = Material::where('periodical_type', $typeValue)
            ->where('material_type', 2)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($articles as $article) {
            $this->decodeAuthors($article);
        }

        return response()->json($articles);
    }

    // Search articles
    public function searchArticles(Request $request) {
        $query = $request->input('query');
    
        if (empty($query)) {
            return response()->json(['message' => 'Please provide a search query.'], 400);
        }
    
        // Normalize the query for case-insensitive search
        $query = '%' . strtolower($query) . '%';
    
        $articles = Material::where(function($queryBuilder) use ($query) {
            $queryBuilder->where('title', 'like', $query)
                         ->orWhere('authors', 'like', $query);
        })
        ->where('material_type', 2)
        ->get();
    
        foreach ($articles as $article) {
            $this->decodeAuthors($article);
        }
    
        return response()->json($articles);
    }

    // View books for student portal
    public function viewBooks() {
        $books = Material::where('material_type', 0)
            ->select(['accession', 'call_number', 'title', 'acquired_date', 'authors', 'image_url', 'price'])
            ->orderByDesc('date_published')
            ->get();

        foreach ($books as $book) {
            $this->processImageURL($book);
            $this->decodeAuthors($book);
        }

        return response()->json($books);
    }

    // View a specific book
    public function viewBook($accession) {
        $book = Material::where('material_type', 0)
            ->where('accession', $accession)
            ->firstOrFail(['accession', 'title', 'authors', 'image_url', 'call_number', 'acquired_date', 'date_published', 'remarks', 'copyright', 'price', 'status',  'volume', 'pages', 'edition']);

        $this->processImageURL($book);
        $this->decodeAuthors($book);

        return response()->json($book);
    }

    // Search books
    public function searchBooks(Request $request) {
        $query = $request->input('query');
    
        if (empty($query)) {
            return response()->json(['message' => 'Please provide a search query.'], 400);
        }
    
        // Normalize the query for case-insensitive search
        $query = '%' . strtolower($query) . '%';
    
        $books = Material::where(function($queryBuilder) use ($query) {
            $queryBuilder->where('title', 'like', $query)
                         ->orWhere('authors', 'like', $query);
        })
        ->where('material_type', 0)
        ->get();
    
        foreach ($books as $book) {
            $this->processImageURL($book);
            $this->decodeAuthors($book);
        }
    
        return response()->json($books);
    }


    // Get all projects
    public function getProjects() {
        $projects = Project::orderByDesc('date_published')
            ->get();

        foreach ($projects as $project) {
            $this->processImageURL($project);
            $this->decodeAuthors($project);
        }

        return response()->json($projects);
    }

 // Get projects by department_short
public function getProjectsByProgram($departmentShort) {
    $projects = Project::join('programs', 'academic_projects.program', '=', 'programs.program_short')
        ->where('programs.department_short', $departmentShort)
        ->orderByDesc('academic_projects.date_published')
        ->select('academic_projects.*')
        ->get();

    foreach ($projects as $project) {
        $this->processImageURL($project);
        $this->decodeAuthors($project);
    }

    return response()->json($projects);
}

    // Get projects by category
    public function getProjectsByCategoryAndDepartment($category, $departmentShort)
    {
        // Ensure $departmentShort is defined and accessible
        // Example usage:
        $projects = Project::where('category', $category)
                           ->whereHas('project_program', function ($query) use ($departmentShort) {
                               $query->where('department_short', $departmentShort);
                           })
                           ->get();
                           
                           foreach ($projects as $project) {
                            $this->processImageURL($project);
                            $this->decodeAuthors($project);
                        }
                    
        return response()->json($projects);
    }

    // Helper method to process image URL
    private function processImageURL(&$material) {
        if ($material->image_url) {
            $material->image_url = self::URL . Storage::url($material->image_url);
        }
    }

    // Helper method to decode authors
    private function decodeAuthors(&$material) {
        $material->authors = json_decode($material->authors, true);
    }

    public function getProjectByAccession($accession)
    {
        $project = Project::where('accession', $accession)->first();

        if ($project) {
            $this->processImageURL($project);
            $this->decodeAuthors($project);
            return response()->json($project);
        } else {
            return response()->json(['error' => 'Project not found'], 404);
        }
    }

      // View audio-visuals for student portal
      public function viewAudioVisuals()
      {
          $audioVisuals = Material::where('material_type', 3)
              ->select(['accession', 'title', 'authors', 'call_number', 'copyright'])
              ->orderByDesc('updated_at')
              ->get();
  
          foreach ($audioVisuals as $audioVisual) {
              $this->processImageURL($audioVisual);
              $this->decodeAuthors($audioVisual);
          }
  
          return response()->json($audioVisuals);
      }
  
      // Get a specific audio-visual material by accession
      public function getAudioVisualByAccession($accession)
      {
          $audioVisual = Material::where('material_type', 3)
              ->where('accession', $accession)
              ->first();
  
          if ($audioVisual) {
              $this->processImageURL($audioVisual);
              $this->decodeAuthors($audioVisual);
              return response()->json($audioVisual);
          } else {
              return response()->json(['error' => 'Audio-visual material not found'], 404);
          }
      }
  
      // Search audio-visuals
      public function searchAudioVisuals(Request $request)
      {
          $query = $request->input('query');
  
          if (empty($query)) {
              return response()->json(['message' => 'Please provide a search query.'], 400);
          }
  
          $audioVisuals = Material::where('title', 'authors', "%{$query}%")
              ->where('material_type', 3)
              ->get();
  
          foreach ($audioVisuals as $audioVisual) {
              $this->processImageURL($audioVisual);
              $this->decodeAuthors($audioVisual);
          }
  
          return response()->json($audioVisuals);
      }


  
}
