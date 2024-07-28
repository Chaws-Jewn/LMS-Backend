<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Material;
use App\Models\Program;


class CatalogingReportController extends Controller
{

    public function countMaterials() {
        // for books
        $books = Material::where('material_type', 0)->get('title');
        $titles = $books->unique('title')->count();
        $volumes = $books->count();

        // for materials
        $materials = Material::where('material_type', 1)->get('periodical_type');

        $p_count = ['journals' => 0, 'magazines' => 0, 'newspapers' => 0];
        foreach($materials as $x){
            if($x->periodical_type == 0)
                $p_count['journals'] = $p_count['journals'] + 1;
            elseif($x->periodical_type == 1)
                $p_count['magazines'] = $p_count['magazines'] + 1;
            elseif($x->periodical_type == 2)
                $p_count['newspapers'] = $p_count['newspapers'] + 1;
        }

        $articles = Material::where('material_type', 2)->get('title')->count();

        return response()->json([
            'titles' => $titles,
            'volumes' => $volumes,
            'journals' => $p_count['journals'],
            'magazines' => $p_count['magazines'],
            'newspapers' => $p_count['newspapers'],
            'articles' => $articles,
        ]);
    }

    public function countProjects(Request $request) {
        // Retrieve projects with their associated programs
        $projectCounts = Project::with('project_program')->get();
    
        // Retrieve departments and initialize the array to 0
        $departments = Program::select('department_short')->groupBy('department_short')->get();
        $departmentsArray = $departments->pluck('department_short')->flip()->map(function() {
            return 0;
        })->all();
    
        // Count the projects for each department
        foreach($projectCounts as $project) {
            if($project->project_program) {
                $department = $project->project_program->department_short;
                if(array_key_exists($department, $departmentsArray)) {
                    $departmentsArray[$department] += 1;
                }
            }
        }
    
        return $departmentsArray;
    }
}
