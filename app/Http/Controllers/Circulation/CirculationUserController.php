<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Borrowmaterial;
use App\Models\Program;
use App\Models\Material;


class CirculationUserController extends Controller
{   
    public function userlist(Request $request){
        $users = User::with('program', 'patron', 'student_program')->whereJsonContains('role', 'student')->get();
    
        $userList = $users->map(function($user) {
            return [
                'id' => $user->id,
                'fname' => $user->first_name,
                'lname' => $user->last_name,
                'gender' => $user->gender == 1 ? 'male' : 'female',
                'email' => $user->domain_email,
                'department' => optional($user->student_program)->department_short ?? 'No department', // Debugging info
                'program' => optional($user->student_program)->program_short ?? 'No program', // Debugging info
            ];
        });
    
        return response()->json($userList);
    }
    public function getUser(Request $request, int $id) {
        $getuser =  User::with('program', 'patron', 'student_program')->findOrFail($id);
        return response()->json([
                'id' => $getuser->id,
                'patron' => $getuser->patron->patron,
                'first_name' => $getuser->first_name,
                'last_name' => $getuser->last_name,
                'department' =>$getuser->student_program->department_short,
                'program' =>$getuser->student_program->program_short,
                'gender' => $getuser->gender,
                'books_allowed' => $getuser->patron->materials_allowed,
                'fine' => $getuser->patron->fine,
                'hours_allowed' => $getuser->patron->hours_allowed,
                
            ]);
    }
    public function getBook(Request $request) {
        $accession = $request->query('accession');
        $title = $request->query('title');
    
        if ($accession) {
            $book = Material::with('book_location')->where('accession', $accession)->firstOrFail();
        } elseif ($title) {
            $book = Material::with('book_location')->where('title', $title)->firstOrFail();
        } else {
            return response()->json(['error' => 'Accession or title must be provided'], 400);
        }
    
        return response()->json($book);
    }

    //for test 
    public function borrowdetail(Request $request)
    {
        $borrow = BorrowMaterial::with('user.program')->get();
        return response()->json($borrow);
    }
}
