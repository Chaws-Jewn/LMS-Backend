<?php

namespace App\Http\Controllers\Cataloging;

use App\Models\Program;
use Illuminate\Http\Request;
use App\Models\Project;
use Exception, DB, Storage, Str;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ActivityLogController;

class ProjectController extends Controller
{
    // const URL = 'http://26.68.32.39:8000';
    const URL = 'http://127.0.0.1:8000';

    public function getProjects() {
        $projects = Project::with('project_program')
        ->orderByDesc('updated_at')
        ->get(['accession', 'program', 'title', 'authors', 'category', 'date_published']);

        foreach($projects as $project) {
            if($project->image_url != null)
                $project->image_url = self::URL .  Storage::url($project->image_url);
            
            $project->authors = json_decode($project->authors);
        }
        
        return $projects;
    }

    public function getByDepartment($department) {
        $all_projects = Project::with('program.department')->get();

        $projects = [];
        foreach($all_projects as $project) {
            if($project->program->department->department == $department) {
                array_push($projects, $project);
            }
        }
        return $projects;
    }

    public function getCounts($department) {
        $projects = Project::with('program.department')->get();

        $keys = [];
        foreach($projects as $project) {
            if(!in_array($project->category, $keys)) {
                foreach($keys as $key) {
                    if($key == $project->program->category) {
                        $keys[$key]++;
                        break;
                    }
                }
            };

        }
    }

    // STUDENT
    public function getByType($department) {
        // for getting by departments -> student portal
        $projects = Project::with(['program'])->orderByDesc('created_at')->get();

        $projects = $projects->where('program', '=', $department);
        
        $projects->each(function ($project) {
            $project->projectAuthors = $project->projectAuthors->sortBy('name')->values();
        });

        foreach($projects as $project) {
            if($project->image_url != null)
                $project->image_url = self::URL .  Storage::url($project->image_url);

            $project->authors = json_decode($project->authors);
            $project->keywords = json_decode($project->keywords);
        }

        return $projects;
    }
    
    public function getProject($id) {
        $project =  Project::with('project_program')->find($id);
        if($project->image_url != null)
                $project->image_url = self::URL .  Storage::url($project->image_url);

            $project->authors = json_decode($project->authors);
            $project->keywords = json_decode($project->keywords);
        
        return $project;
    }
    
    /* DATA PROCESSING */
    public function add(Request $request) {

        // VALIDATION
        $request->validate([
            'accession' => 'required|string|max:20',
            'category' => 'required|string|max:125',
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:1024',
            'program' => 'required|string|max:20',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date_published' => 'required|date',
            'language' => 'required|string|max:25',
            'abstract' => 'nullable|string|max:2048',
            'keywords' => 'required|string|max:125'
        ]);        

        // return 'after validation';
        $model = new Project();
        try {
            $model->fill($request->except('image_url', 'authors'));
        } catch (Exception) {
            return response()->json(['Error' => 'Invalid form request. Check values if on correct data format.', 400]);
        }

        // ADD COVER
        $ext = $request->file('image_url')->extension();

        // Check file extension and raise error
        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
            return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
        }

        // Store image and save path
        $path = $request->file('image_url')->store('public/images/projects/covers');

        $model->image_url = $path;

        // ADD AUTHORS
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        sort($authors);

        $model->authors = json_encode($authors);
        
        $model->save();
        
        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Added project of accession ' . $request->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 201);
    }

    public function update(Request $request, $id) {
        
        // VALIDATION
        $request->validate([
            'accession' => 'required|string|max:20',
            'category' => 'required|string|max:125',
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:1024',
            'program' => 'required|string|max:20',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date_published' => 'required|date',
            'language' => 'required|string|max:25',
            'abstract' => 'nullable|string|max:2048',
            'keywords' => 'required|string|max:125'
        ]);

        $model = Project::findOrFail($id);

        try {
            $model->fill($request->except('image_url', 'authors'));
        } catch (Exception) {
            return response()->json(['Error' => 'Invalid form request. Check values if on correct data format.'], 400);
        }

        if($request->image_url != null) {
            $ext = $request->file('image_url')->extension();

            // Check file extension and raise error
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                return response()->json(['Error' => 'Invalid image format. Only PNG, JPG, and JPEG formats are allowed.'], 415);
            }

            $path = $request->file('image_url')->store('public/images/projects');
            $model->image_url = $path;
        }
        
        $authors = json_decode($request->authors, true);

        foreach($authors as &$author) {
            $author = Str::title($author);
        }

        sort($authors);

        $model->authors = json_encode($authors);
        
        $model->save();

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated project of accession ' . $model->accession;

        $log->savePersonnelLog($logParam);

        return response()->json($model, 200);
    }
}
