<?php

namespace App\Http\Controllers;

use App\Models\Resident;       // old DB
use App\Models\ResidentNew;    // new DB

class ResidentTestController extends Controller
{
    public function index()
    {
        // Old DB residents
        $oldResidents = Resident::select('resident_id', 'first_name', 'middle_name', 'last_name')
            ->limit(10)
            ->get();

        // New DB residents
        $newResidents = ResidentNew::select('id', 'first_name', 'middle_name', 'surname')
            ->where('is_deleted', 0)
            ->limit(10)
            ->get();

        return view('residents.test', compact('oldResidents', 'newResidents'));
    }
}