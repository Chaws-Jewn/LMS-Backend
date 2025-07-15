<?php

namespace App\Http\Controllers\Circulation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patron;

class PatronController extends Controller
{
    public function index()
    {
        $patrons = Patron::all();

        return response()->json($patrons);
    }

    public function getPatronById($id)
    {
        $patron = Patron::findorfail($id);

        return response()->json($patron);
    }

    public function edit($id)
    {
        return Patron::findorfail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fine' => 'required|numeric|gt:-1',
            'days_allowed' => 'nullable|integer|gt:-1',
            'hours_allowed' => 'required|integer|between:0,23',
        ]);

        $patron = Patron::findorfail($id);

        $patron->fine = $request->fine;
        $hours = ($request->days_allowed * 24) + $request->hours_allowed;
        $patron->hours_allowed = $hours;

        $patron->save();

        return response()->json(['success' => 'Patron has been successfully updated']);
    }
}
