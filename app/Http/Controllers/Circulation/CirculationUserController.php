<?php

namespace App\Http\Controllers\Circulation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Material;


class CirculationUserController extends Controller
{   
    public function userlist(Request $request){
        $users = User::with( 'patron')->where('role', '["user"]')->get();
        return response()->json($users, 200);
    }
    public function getUser(Request $request, int $id) {
        return User::with('program', 'patron')->findOrFail($id);
    }
    public function getBook(Request $request, string $accession) {
        return Material::with('book_location')->findOrFail($accession);
    }
}
