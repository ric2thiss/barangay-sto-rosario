<?php

namespace App\Http\Controllers;

use App\Models\IncidentArea;
use App\Models\BlotterPurok;
use Illuminate\Http\Request;

class IncidentAreaController extends Controller
{
  public function index(Request $request)
{
    $search = $request->input('search', '');

    $areas = IncidentArea::when($search, fn($q) => $q->where('name', 'like', '%' . $search . '%'))
        ->orderBy('name')
        ->paginate(20)
        ->withQueryString();

    // Attach usage counts manually
    $ids = $areas->pluck('id');
    $counts = \DB::table('blotter_purok')
        ->whereIn('area_id', $ids)
        ->select('area_id', \DB::raw('COUNT(*) as total'))
        ->groupBy('area_id')
        ->pluck('total', 'area_id');

    $areas->each(fn($a) => $a->blotter_puroks_count = $counts->get($a->id, 0));

    return view('incident_areas.index', compact('areas', 'search'));
}

public function destroy(IncidentArea $incidentArea)
{
    $usageCount = \DB::table('blotter_purok')->where('area_id', $incidentArea->id)->count();

    if ($usageCount > 0) {
        return back()->with('error', 'Cannot delete "' . $incidentArea->name . '" — it is linked to ' . $usageCount . ' blotter record(s). Reassign them first.');
    }

    $incidentArea->delete();

    return back()->with('success', 'Area deleted.');
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_areas,name'],
        ]);

        IncidentArea::create($validated);

        return back()->with('success', 'Area "' . $validated['name'] . '" added successfully.');
    }

    public function update(Request $request, IncidentArea $incidentArea)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_areas,name,' . $incidentArea->id],
        ]);

        $incidentArea->update($validated);

        return back()->with('success', 'Area renamed to "' . $validated['name'] . '".');
    }

   
}