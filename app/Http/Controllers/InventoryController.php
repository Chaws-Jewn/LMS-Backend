<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Book;
use App\Models\Inventory;
use App\Models\Material;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InventoryController extends Controller
{

    //0 -> available, 1 -> unreturned, 2 -> missing, 3 -> unlabeled, 4 -> damaged
    public function getBookInventory($status)
    {
        if(!in_array($status, [0, 1, 2, 3, 4])){
            return response()->json(['error' => 'Page not found'], 404);
        }

        $books = Material::select('accession', 'location', 'title', 'authors', 'status')
                    ->where('material_type', 0)
                    ->where('status', $status)
                    ->orderByDesc('created_at')
                    ->get();

        foreach($books as $book) {
            $book->authors = json_decode($book->authors);
        }

        return $books;
    }

    public function searchBookInventory(Request $request, $status)
    {
        $search = $request->input('search', '');

        if(!in_array($status, [0, 1, 2, 3, 4])){
            return response()->json(['error' => 'Page not found'], 404);
        }

        $books = Material::select('accession', 'location', 'title', 'authors', 'status')
                    ->where('material_type', 0)
                    // ->where('status', $status)
                    ->where('accession', $search)
                    ->orderByDesc('created_at')
                    ->get();

        foreach($books as $book) {
            $book->authors = json_decode($book->authors);
        }



        return $books;
    }

    public function updateBookStatus(Request $request, $id) {
        $data = Validator::make($request->all(), [
            'status' => 'required|numeric|between:0,4'
        ]);

        if($data->fails()) {
            return response()->json(['errors', $data->errors()], 400);
        }

        $book = Material::findorfail($id);
        $book->update($data->validated());
        $book->save();

        // Logging the update
        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Maintenance';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Updated book status to ' . $request->status . ' for book ID ' . $id;

        $log->savePersonnelLog($logParam);

        return response()->json(['success' => 'Item has been updated.'], 200);
    }

    public function clearBooksHistory() {
        Material::where('material_type', 0)
                // ->where('status', 0)
                ->update(['status' => 0]);

        return response()->json(['success' => 'History has been cleared.'], 200);
    }
}
