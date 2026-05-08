<?php

namespace App\Http\Controllers;

use App\Models\IncidentArea;
use App\Models\Purok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidentAreaController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->input('search', '');
        $purokSearch = $request->input('purok_search', '');

        // ── Areas ──
        $areas = IncidentArea::when($search, fn($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(20, ['*'], 'areas_page')
            ->withQueryString();

        $ids    = $areas->pluck('id');
        $counts = DB::table('blotter_purok')
            ->whereIn('area_id', $ids)
            ->select('area_id', DB::raw('COUNT(*) as total'))
            ->groupBy('area_id')
            ->pluck('total', 'area_id');

        $areas->each(fn($a) => $a->blotter_puroks_count = $counts->get($a->id, 0));

        // ── Puroks ──
        $puroks = Purok::when($purokSearch, fn($q) => $q->where('purok_name', 'like', '%' . $purokSearch . '%'))
            ->orderBy('purok_name')
            ->paginate(20, ['*'], 'puroks_page')
            ->withQueryString();

        // Use the existing residents() relationship on each Purok — it knows the correct FK/column
      $puroks->each(function ($p) {
    try {
        $p->residents_count = $p->residents()->active()->count();
    } catch (\Exception $e) {
        $p->residents_count = 0;
    }
});

        return view('incident_areas.index', compact('areas', 'search', 'puroks', 'purokSearch'));
    }

    // ── Areas CRUD ──

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

    public function destroy(IncidentArea $incidentArea)
    {
        $usageCount = DB::table('blotter_purok')->where('area_id', $incidentArea->id)->count();

        if ($usageCount > 0) {
            return redirect()->route('incident-areas.index')->with('error', 'Cannot delete "' . $incidentArea->name . '" — it is linked to ' . $usageCount . ' blotter record(s). Reassign them first.');
        }

        $incidentArea->delete();

        return back()->with('success', 'Area deleted.');
    }

    // ── Puroks CRUD ──

    public function storePurok(Request $request)
    {
        $validated = $request->validate([
            'purok_name' => ['required', 'string', 'max:255', 'unique:puroks,purok_name'],
        ]);

        Purok::create($validated);

        return redirect()->route('incident-areas.index')->with('success', 'Purok "' . $validated['purok_name'] . '" added successfully.');
    }

    public function updatePurok(Request $request, Purok $purok)
    {
        $validated = $request->validate([
            'purok_name' => ['required', 'string', 'max:255', 'unique:puroks,purok_name,' . $purok->purok_id . ',purok_id'],
        ]);

        $purok->update($validated);

        return redirect()->route('incident-areas.index')->with('success', 'Purok renamed to "' . $validated['purok_name'] . '".');
    }

public function destroyPurok(Purok $purok)
{
    try {
        $residentCount = $purok->residents()->active()->count();
    } catch (\Exception $e) {
        $residentCount = 0;
    }

    if ($residentCount > 0) {
        return redirect()->route('incident-areas.index')->with('error', 'Cannot delete "' . $purok->purok_name . '" — it has ' . $residentCount . ' resident(s) assigned. Reassign them first.');
    }

    $purok->delete();

    return redirect()->route('incident-areas.index')->with('success', 'Purok "' . $purok->purok_name . '" deleted.');
}
}