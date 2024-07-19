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
        $users = User::with( 'patron')->whereJsonContains('role', 'student')->get();
        return response()->json($users->map(function($users){
            return[
                'id' => $users->id,
                'fname' => $users->first_name,
                'lname' => $users->last_name,
                'gender' => $users->gender == 1 ? 'male' : 'female',
                'email' => $users->domain_email,
                'department' => $users->program,
            ];
        }));
    }
    public function getUser(Request $request, int $id) {
        return User::with('program', 'patron')->findOrFail($id);
    }
    public function getBook(Request $request, string $accession) {
        return Material::with('book_location')->findOrFail($accession);
    }

    //for test 
    public function borrowdetail(Request $request)
    {
        $borrow = BorrowMaterial::with('user.program')->get();
        return response()->json($borrow);
    }
}
