<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\Resident;
use App\Models\Purok;
use App\Models\IncidentType;
use App\Services\KpFormDocxExporter;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class BlotterController extends Controller
{
    private function kpForms(): array
    {
        return config('kp_forms.forms', []);
    }

    private function getFormById(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }
        foreach ($this->kpForms() as $form) {
            if ((string) $form['id'] === (string) $id) {
                return $form;
            }
            if (isset($form['label']) && (string) $form['label'] === (string) $id) {
                return $form;
            }
        }

        return null;
    }

    /**
     * Display a listing of the resource.
     */
    
    public function index(Request $request)
{
    $status   = $request->input('status');
    $form     = $request->input('form');
    $sort     = $request->input('sort', 'priority');
    $search   = $request->input('search');
    $approval = $request->input('approval');

    $query = BlotterRecord::with('recorder');

    if ($status) {
        $query->where('status', $status);
    }

    if ($approval === 'pending') {
        $query->where('status', 'Pending');
    } elseif ($approval === 'approved') {
        $query->where('status', 'Completed');
    }

    if ($form) {
        $query->where('form_number', 'like', '%' . $form . '%');
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('form_number', 'like', '%' . $search . '%')
              ->orWhere('purpose', 'like', '%' . $search . '%')
              ->orWhere('incident_type', 'like', '%' . $search . '%');
        });
    }

    if ($sort === 'date_asc') {
        $query->orderBy('created_at', 'asc');
    } elseif ($sort === 'date_desc') {
        $query->orderBy('created_at', 'desc');
    } else {
        $query->orderByRaw("CASE status WHEN 'Pending' THEN 0 WHEN 'Active' THEN 1 ELSE 2 END")
              ->latest('created_at');
    }

    // ✅ $records is now defined before any ->each() calls
    $records = $query->paginate(10)->withQueryString();

    // --- Resident names (complainant / respondent) ---
    $residentFields = [
        'complainant_id', 'complainant_ids',
        'respondent_id',  'respondent_ids',
        'appointee_id',
        'member_id',      'member_ids',
        'witness_ids',
        'resident_ids',
        'respondent_id_1', 'respondent_id_2',
    ];

    $allIds = collect();
    $records->each(function ($record) use ($allIds, $residentFields) {
        $formData = $record->form_data ?? [];
        foreach ($residentFields as $field) {
            $val = $formData[$field] ?? null;
            if ($val) {
                foreach ((array) $val as $id) {
                    if ($id) $allIds->push($id);
                }
            }
        }
    });

    $residents = Resident::whereIn('id', $allIds->unique()->values())
        ->get()
        ->keyBy('resident_id');

    $records->each(function ($record) use ($residents, $residentFields) {
        $formData = $record->form_data ?? [];

        $cIds = $record->getRawOriginal('complainant_id')
            ? [$record->getRawOriginal('complainant_id')]
            : array_values(array_filter((array) ($formData['complainant_ids'] ?? $formData['complainant_id'] ?? [])));

        $rIds = $record->getRawOriginal('respondent_id')
            ? [$record->getRawOriginal('respondent_id')]
            : array_values(array_filter((array) ($formData['respondent_ids'] ?? $formData['respondent_id'] ?? [])));

        if (empty($cIds)) {
            foreach (['appointee_id', 'member_id', 'member_ids', 'resident_ids'] as $f) {
                $val = array_values(array_filter((array) ($formData[$f] ?? [])));
                if (!empty($val)) { $cIds = $val; break; }
            }
        }

        if (empty($rIds)) {
            foreach (['witness_ids', 'respondent_id_1'] as $f) {
                $val = array_values(array_filter((array) ($formData[$f] ?? [])));
                if (!empty($val)) { $rIds = $val; break; }
            }
        }

        $record->complainant_names = implode(', ', array_filter(
            array_map(fn($id) => $residents->get($id)?->full_name, $cIds)
        )) ?: 'N/A';

        $record->respondent_names = implode(', ', array_filter(
            array_map(fn($id) => $residents->get($id)?->full_name, $rIds)
        )) ?: 'N/A';
    });

    // ✅ Recorder name + resident flag — goes AFTER $records is defined
    $records->each(function ($record) {
        $user = $record->recorder;
        if ($user) {
            $record->recorder_name        = $user->name;
            $record->recorder_is_resident = $user->is_resident;
        } else {
            $record->recorder_name        = 'N/A';
            $record->recorder_is_resident = null;
        }
    });

    $kpForms = $this->kpForms();
    $filters = compact('status', 'form', 'sort', 'search', 'approval');

    return view('blotter.index', compact('records', 'kpForms', 'filters'));
}
   

public function create()
{
    $kpForms = $this->kpForms();
    $isPendingOnly = auth()->user()->hasRole('Resident');
    $oldResidents = collect();
    $puroks = Purok::orderByRaw("CAST(REGEXP_SUBSTR(purok_name, '[0-9]+') AS UNSIGNED)")->get();
    $incidentTypes = \App\Models\IncidentType::orderBy('name')->get();
 
    // Build a map of purok_id => [['id' => ..., 'name' => ...], ...]
    // used by JS to populate the area dropdown when a purok is selected
// AFTER (all areas available to every purok):
$allAreas = \App\Models\IncidentArea::orderBy('name')->get()
    ->map(fn($a) => ['id' => $a->id, 'name' => $a->name])
    ->values()
    ->toArray();

$purokAreas = $puroks->mapWithKeys(fn($p) => [
    $p->purok_id => $allAreas,
]);
 
    if (old()) {
        $oldIds = array_filter(array_merge(
            (array) old('complainant_id'),
            (array) old('respondent_id'),
            (array) old('appointee_id'),
            (array) old('member_id'),
            array_merge(...array_map('array_values', array_filter([
                old('member_ids'), old('complainant_ids'), old('respondent_ids'),
                old('witness_ids'), old('resident_ids'),
            ]))),
        ));
        if (!empty($oldIds)) {
            $oldResidents = Resident::whereIn('id', array_unique($oldIds))
                ->get()
                ->keyBy('id');
        }
    }
 
    return view('blotter.create', compact(
        'kpForms', 'isPendingOnly', 'oldResidents', 'puroks', 'purokAreas', 'incidentTypes'
    ))->with('existingPurok', null);
}
    /**
     * AJAX: search residents by name, returns JSON for select2-style dropdowns.
     */
   public function searchResidents(\Illuminate\Http\Request $request)
{
    $q = trim($request->input('q', ''));

    $query = Resident::query();  // no select() — let global scope handle it

    if ($q !== '') {
        $query->where(function ($sub) use ($q) {
            $sub->where('surname', 'like', $q . '%')           // ← real column
                ->orWhere('first_name', 'like', $q . '%')
                ->orWhereRaw("CONCAT(surname, ', ', first_name) LIKE ?", ['%' . $q . '%']); // ← real column
        });
    }

    $results = $query
        ->orderBy('surname')      // ← real column
        ->orderBy('first_name')
        ->limit(30)
        ->get()
        ->map(fn($r) => [
            'id'   => $r->id,                // ← new PK
            'text' => $r->last_name . ', ' . $r->first_name   // ← accessor works here
                . ($r->middle_name ? ' ' . substr($r->middle_name, 0, 1) . '.' : '')
                . ($r->suffix ? ' ' . $r->suffix : ''),
        ]);

    return response()->json($results);
}

    /**
     * Store a newly created resource in storage.
     */   
    
public function store(Request $request)
{
    $formId = $request->input('form_number');
    $form = $this->getFormById($formId);
    if (!$form) {
        return back()->withErrors(['form_number' => 'Invalid form selected.'])->withInput();
    }

    $rules = $this->validationRulesForForm($form);
    $rules['evidence_pics']   = ['nullable', 'array', 'max:5'];
    $rules['evidence_pics.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    $rules['evidence_link']   = ['nullable', 'string', 'max:1000'];
    $rules['purok_id']      = ['nullable', 'exists:puroks,purok_id'];
    $rules['area_id']       = ['nullable', 'exists:incident_areas,id'];
$rules['new_area_name'] = ['nullable', 'string', 'max:255'];
    $rules['incident_type_ids']   = ['nullable', 'array'];
$rules['incident_type_ids.*'] = ['exists:incident_types,id'];

    $validated = $request->validate($rules);

    $payload = $this->buildPayloadFromValidated($form, $validated);
    $payload['recorded_by'] = auth()->user()->user_id;

    // store() — no $blotter variable here, this is a NEW record
    if ($request->hasFile('evidence_pics')) {
        $paths = [];
        foreach ($request->file('evidence_pics') as $file) {
            $paths[] = $file->store('blotter/evidence', 'public');
        }
        $payload['evidence_pic'] = $paths; // plain array — cast handles JSON
    } else {
        $payload['evidence_pic'] = null;
    }

    $payload['evidence_link'] = !empty($validated['evidence_link'])
        ? $validated['evidence_link']
        : null;

    $blotter = BlotterRecord::create($payload);
    // $this->generateDocx($blotter);

$purokId = $validated['purok_id'] ?? null;
$areaId  = $validated['area_id'] ?? null;

if ($purokId) {
    $newAreaName = trim($validated['new_area_name'] ?? '');
    if (!$areaId && $newAreaName !== '') {
        $areaModel = \App\Models\IncidentArea::firstOrCreate(['name' => $newAreaName]);
        $areaId    = $areaModel->id;
    }
    $blotter->puroks()->attach($purokId, ['area_id' => $areaId ?: null]);
}

// Incident type pivot
$selectedTypes = array_filter((array) $request->input('incident_type_ids', []));
if (!empty($selectedTypes)) {
    $blotter->incidentTypes()->sync($selectedTypes);
}
$indexRoute = auth()->user()->hasRole('Resident')
        ? 'blotter.resident_index'
        : 'blotter.index';
// dd([
//     'has_file'  => $request->hasFile('evidence_pics'),
//     'all_files' => $request->allFiles(),
// ]);
 
    return redirect()->route('blotter.index')
        ->with('success', 'Blotter record created successfully.');
}


    /**
     * Display the specified resource.
     */public function show(BlotterRecord $blotter)
{
    $blotter->load(['complainant', 'respondent', 'recordedBy', 'puroks']);
    $form = $this->getFormById($blotter->form_id);
    $formDataResolved = $this->resolveFormDataForDisplay($blotter);

    if (! $blotter->complainant_id || $blotter->complainant?->first_name === 'Unknown') {
        $formData = $blotter->form_data ?? [];
        $cId = $formData['complainant_ids'][0] ?? $formData['complainant_id'][0]
            ?? $formData['appointee_id'] ?? $formData['member_id']
            ?? $formData['member_ids'][0] ?? $formData['resident_ids'][0] ?? null;
        $rId = $formData['respondent_ids'][0] ?? $formData['respondent_id'][0]
            ?? $formData['witness_ids'][0] ?? $formData['respondent_id_1'] ?? null;
        if ($cId) $blotter->setRelation('complainant', Resident::find($cId));
        if ($rId) $blotter->setRelation('respondent', Resident::find($rId));
    }

    // Resolve area name from pivot area_id
    $locationPurok = $blotter->puroks->first();
    $locationArea  = null;
    if ($locationPurok && $locationPurok->pivot->area_id) {
        $locationArea = \App\Models\IncidentArea::find($locationPurok->pivot->area_id)?->name;
    }

    return view('blotter.show', compact('blotter', 'form', 'formDataResolved', 'locationPurok', 'locationArea'));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlotterRecord $blotter)
    {
        $kpForms = $this->kpForms();
        $form = $this->getFormById($blotter->form_id ?? $blotter->form_number);

            $puroks    = Purok::orderByRaw("CAST(REGEXP_SUBSTR(purok_name, '[0-9]+') AS UNSIGNED)")->get();
    $allAreas = \App\Models\IncidentArea::orderBy('name')->get()
    ->map(fn($a) => ['id' => $a->id, 'name' => $a->name])
    ->values()
    ->toArray();

$purokAreas = $puroks->mapWithKeys(fn($p) => [
    $p->purok_id => $allAreas,
]);
$incidentTypes    = IncidentType::orderBy('name')->get();
$blotter->load('puroks', 'incidentTypes');
$existingPurok    = $blotter->puroks->first();
$selectedTypeIds  = $blotter->incidentTypes->pluck('id')->toArray();

 

        // Collect only the resident IDs actually used in this record
        $formData = $blotter->form_data ?? [];
        $usedIds = array_unique(array_filter(array_merge(
            [$blotter->complainant_id, $blotter->respondent_id],
            (array) ($formData['appointee_id'] ?? []),
            (array) ($formData['member_id'] ?? []),
            $formData['member_ids'] ?? [],
            $formData['complainant_ids'] ?? [],
            $formData['respondent_ids'] ?? [],
            $formData['witness_ids'] ?? [],
            $formData['ids'] ?? [],
        )));

        $residents = Resident::select('id', 'first_name', 'surname', 'middle_name', 'suffix')
            ->whereIn('id', $usedIds)
            ->get()
            ->keyBy('id');

   return view('blotter.edit', compact(
    'blotter', 'residents', 'kpForms', 'form',
    'puroks', 'purokAreas', 'incidentTypes', 'selectedTypeIds'
))->with('existingPurok', $existingPurok);
    }

    /**
     * Update the specified resource in storage.
     */
  

    
public function update(Request $request, BlotterRecord $blotter)
{ 
    $formId = $request->input('form_number');
    $form = $this->getFormById($formId);
    if (!$form) {
        return back()->withErrors(['form_number' => 'Invalid form selected.'])->withInput();
    }

    $rules = $this->validationRulesForForm($form);
$keepCount = count($request->input('keep_pics', []));
$maxNew    = max(0, 5 - $keepCount);
$rules['evidence_pics']   = ['nullable', 'array', 'max:' . $maxNew];
    $rules['evidence_pics.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    $rules['evidence_link']   = ['nullable', 'string', 'max:1000'];
    $rules['keep_pics']       = ['nullable', 'array'];
    $rules['keep_pics.*']     = ['string'];
    $rules['purok_id']      = ['nullable', 'exists:puroks,purok_id'];
$rules['area_id']       = ['nullable', 'exists:incident_areas,id'];
$rules['new_area_name'] = ['nullable', 'string', 'max:255'];
$rules['incident_type_ids']   = ['nullable', 'array'];
$rules['incident_type_ids.*'] = ['exists:incident_types,id'];

    $validated = $request->validate($rules);

    $payload = $this->buildPayloadFromValidated($form, $validated);

    // Determine which existing pics to keep
    $oldPics  = $this->decodePics($blotter->evidence_pic);
    $keepPics = $validated['keep_pics'] ?? [];

    // Delete removed pics from storage
    foreach ($oldPics as $old) {
        if (!in_array($old, $keepPics)) {
            Storage::disk('public')->delete($old);
        }
    }

    // Merge kept pics + any newly uploaded pics
    $newPaths = [];
    if ($request->hasFile('evidence_pics')) {
        foreach ($request->file('evidence_pics') as $file) {
            $newPaths[] = $file->store('blotter/evidence', 'public');
        }
    }

    $finalPics = array_merge($keepPics, $newPaths);
    $payload['evidence_pic'] = array_values($finalPics);
    $payload['evidence_link'] = $validated['evidence_link'] ?? null;
// dd([
//     'keepPics'   => $keepPics,
//     'newPaths'   => $newPaths,
//     'finalPics'  => $finalPics,
//     'payload_ep' => $payload['evidence_pic'],
//     'has_file'   => $request->hasFile('evidence_pics'),
//     'files'      => $request->allFiles(),
// ]);
    $blotter->update($payload);


$purokId = $validated['purok_id'] ?? null;
$areaId  = $validated['area_id'] ?? null;

$blotter->puroks()->detach();
if ($purokId) {
    $newAreaName = trim($validated['new_area_name'] ?? '');
    if (!$areaId && $newAreaName !== '') {
        $areaModel = \App\Models\IncidentArea::firstOrCreate(['name' => $newAreaName]);
        $areaId    = $areaModel->id;
    }
    $blotter->puroks()->attach($purokId, ['area_id' => $areaId ?: null]);
}

// Incident type pivot
$selectedTypes = array_filter((array) $request->input('incident_type_ids', []));
$blotter->incidentTypes()->sync($selectedTypes);


    return redirect()->route('blotter.index')
        ->with('success', 'Blotter record updated successfully.');
}

public function updateStatus(Request $request, BlotterRecord $blotter)
{
    $status = $request->input('status');

    if (!in_array($status, ['Pending', 'Active', 'Approved', 'Released', 'Completed', 'Dismissed'])) {
        return back()->with('error', 'Invalid status.');
    }

    $blotter->update(['status' => $status]);

    return back()->with('success', "Blotter record marked as {$status}.");
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlotterRecord $blotter)
    {
        $filename = $this->docxFilename($blotter);
        $filePath = 'public/blotter_docs/'.$filename;

        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        $blotter->delete();

        return redirect()->route('blotter.index')
            ->with('success', 'Blotter record deleted successfully.');
    }

    /**
     * Download the DOCX file for the blotter.
     */
    public function downloadDocx(BlotterRecord $blotter)
    {
        // Use direct template export to ensure we get the original template
        $exporter = new KpFormDocxExporter;

        // Create a temporary file for the DOCX
        $tempFile = tempnam(sys_get_temp_dir(), 'kp_form_').'.docx';

        try {
            // Try to export using the template
            if ($exporter->exportToFile($blotter, $tempFile)) {
                $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $blotter->form_number);
                $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

                return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
            } else {
                // Graceful fallback: generate DOCX and serve download
                $this->generateDocx($blotter);

                $docxFilename = $this->docxFilename($blotter);
                $docxPath = storage_path('app/public/blotter_docs/'.$docxFilename);

                if (file_exists($docxPath)) {
                    $fallbackTemp = tempnam(sys_get_temp_dir(), 'kp_form_').'.docx';
                    copy($docxPath, $fallbackTemp);

                    $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $blotter->form_number);
                    $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                    $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

                    return Response::download($fallbackTemp, $filename)->deleteFileAfterSend(true);
                }

                $phpWord = new PhpWord;
                $section = $phpWord->addSection();
                $section->addText('Template export failed and fallback file not found.');
                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $tmp = tempnam(sys_get_temp_dir(), 'kp_fail_').'.docx';
                $writer->save($tmp);
                $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $blotter->form_number);
                $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

                return Response::download($tmp, $filename)->deleteFileAfterSend(true);
            }
        } catch (Exception $e) {
            $phpWord = new PhpWord;
            $section = $phpWord->addSection();
            $section->addText('Failed to export: '.$e->getMessage());
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $tmp = tempnam(sys_get_temp_dir(), 'kp_fail_').'.docx';
            $writer->save($tmp);
            $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $blotter->form_number);
            $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
            $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

            return Response::download($tmp, $filename)->deleteFileAfterSend(true);
        }
    }

    public function downloadPdf(BlotterRecord $blotter)
    {
        $exporter = new KpFormDocxExporter;
        $tempDocx = tempnam(sys_get_temp_dir(), 'kp_form_') . '.docx';

        try {
            $exported = $exporter->exportToFile($blotter, $tempDocx);

            if (!$exported || !file_exists($tempDocx)) {
                abort(500, 'Failed to generate DOCX for PDF conversion.');
            }

            // Convert DOCX to PDF using DomPDF (via HTML writer)
            $phpWord = IOFactory::load($tempDocx);
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');

            $htmlTempFile = tempnam(sys_get_temp_dir(), 'kp_form_html_');
            $htmlWriter->save($htmlTempFile);

            $htmlContent = file_get_contents($htmlTempFile);
            @unlink($htmlTempFile);

            $pdf = PDF::loadHTML($htmlContent)
                ->setPaper('letter', 'portrait');

            $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $blotter->form_number);
            $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
            $filename = 'KP_Form_' . $safeForm . '_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (Exception $e) {
            abort(500, 'PDF export failed: ' . $e->getMessage());
        } finally {
            if (file_exists($tempDocx)) {
                @unlink($tempDocx);
            }
        }
    }

    public function exportForm($blotterId, $format = 'docx', $template = 'kp_form')
    {
        $blotter = BlotterRecord::with(['complainant', 'respondent'])->findOrFail($blotterId);

        $validFormats = ['pdf', 'docx'];
        $validTemplates = ['kp_form'];

        if (! in_array($format, $validFormats) || ! in_array($template, $validTemplates)) {
            abort(404, 'Invalid format or template');
        }

        $exportData = [
            'blotter' => $blotter,
            'complainant' => $blotter->complainant,
            'respondent' => $blotter->respondent,
            'formData' => $blotter->form_data ?? [],
            'issueDate' => now()->format('F j, Y'),
        ];

        if ($format === 'pdf') {
            return $this->exportFormToPdf($exportData, $template);
        } else {
            return $this->exportFormToWord($exportData, $template);
        }
    }
  private function decodePics(mixed $raw): array
    {
        if (empty($raw)) return [];
        if (is_array($raw)) return $raw;

        // Strip extra escaping if present (legacy records)
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try unescaping once more
        $decoded = json_decode(stripslashes($raw), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
    private function exportFormToPdf($data, $template)
    {
        // For PDF export, generate the DOCX first then convert to PDF
        $exporter = new KpFormDocxExporter;

        // Create a temporary file for the DOCX
        $tempDocx = tempnam(sys_get_temp_dir(), 'kp_form_').'.docx';

        try {
            // Try to export using the template
            if ($exporter->exportToFile($data['blotter'], $tempDocx)) {
                // Convert the generated DOCX to PDF
                $phpWord = IOFactory::load($tempDocx);

                // Save to HTML temporarily
                $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                $htmlTempFile = tempnam(sys_get_temp_dir(), 'kp_form_html_');
                $htmlWriter->save($htmlTempFile);

                // Read the HTML content
                $htmlContent = file_get_contents($htmlTempFile);

                // Clean up temporary HTML file
                unlink($htmlTempFile);

                // Convert HTML to PDF using DomPDF
                $pdf = PDF::loadHTML($htmlContent)
                    ->setPaper('letter', 'portrait');

                // Clean up Word temp file
                unlink($tempDocx);

                $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $data['blotter']->form_number);
                $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.pdf';

                return $pdf->download($filename);
            } else {
                // Fallback to generating DOCX manually
                $this->generateDocx($data['blotter']);

                // Get the path to the generated DOCX
                $docxFilename = $this->docxFilename($data['blotter']);
                $docxPath = storage_path('app/public/blotter_docs/'.$docxFilename);

                if (file_exists($docxPath)) {
                    $phpWord = IOFactory::load($docxPath);

                    // Save to HTML temporarily
                    $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                    $htmlTempFile = tempnam(sys_get_temp_dir(), 'kp_form_html_');
                    $htmlWriter->save($htmlTempFile);

                    // Read the HTML content
                    $htmlContent = file_get_contents($htmlTempFile);

                    // Clean up temporary HTML file
                    unlink($htmlTempFile);

                    // Convert HTML to PDF using DomPDF
                    $pdf = PDF::loadHTML($htmlContent)
                        ->setPaper('letter', 'portrait');

                    $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $data['blotter']->form_number);
                    $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                    $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.pdf';

                    return $pdf->download($filename);
                } else {
                    return response()->json(['error' => 'Failed to generate PDF: DOCX file not found'], 500);
                }
            }
        } catch (Exception $e) {
            // Clean up temp files
            if (file_exists($tempDocx)) {
                unlink($tempDocx);
            }

            return response()->json(['error' => 'Failed to convert to PDF: '.$e->getMessage()], 500);
        }
    }

    private function exportFormToWord($data, $template)
    {
        // Use the KpFormDocxExporter to generate the DOCX file
        $exporter = new KpFormDocxExporter;

        // Create a temporary file for the DOCX
        $tempFile = tempnam(sys_get_temp_dir(), 'kp_form_').'.docx';

        try {
            // Try to export using the template
            if ($exporter->exportToFile($data['blotter'], $tempFile)) {
                $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $data['blotter']->form_number);
                $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

                return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
            } else {
                // Fallback: generate the DOCX manually and then serve it
                $this->generateDocx($data['blotter']);

                // Get the path to the generated DOCX
                $docxFilename = $this->docxFilename($data['blotter']);
                $docxPath = storage_path('app/public/blotter_docs/'.$docxFilename);

                if (file_exists($docxPath)) {
                    $tempFile = tempnam(sys_get_temp_dir(), 'kp_form_').'.docx';
                    copy($docxPath, $tempFile);

                    $safeForm = preg_replace('/[\/\\\\]/', '-', (string) $data['blotter']->form_number);
                    $safeForm = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeForm);
                    $filename = 'KP_Form_'.$safeForm.'_'.now()->format('Y-m-d').'.docx';

                    return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
                } else {
                    return response()->json(['error' => 'Failed to generate DOCX: file not found'], 404);
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to export to Word: '.$e->getMessage()], 500);
        }
    }

    /**
     * Return validation rules for a KP form.
     */
private function validationRulesForForm(array $form): array
{
    $rules = [
        'form_number' => 'required|string|max:32',
        'status' => 'required|in:Pending,Completed,Dismissed',
    ];

    foreach ($form['fields'] ?? [] as $field) {
        $key = $field['name'];
        $required = $field['required'] ?? true;
        $base = [];

        $db = config('database.connections.sto_rosario.database');

        if ($field['type'] === 'resident') {
            $base = ['nullable', "exists:{$db}.residents,id"];  // ← fixed
        } elseif ($field['type'] === 'resident_multi') {
            $base = ['nullable', 'array'];
            $rules[$key.'.*'] = "nullable|exists:{$db}.residents,id";  // ← fixed
            if (isset($field['max']) && is_int($field['max'])) {
                $base[] = 'max:'.$field['max'];
            }
            if ($key === 'complainant_ids' || $key === 'respondent_ids') {
                $base[] = 'min:1';
                if (! isset($field['max'])) {
                    $base[] = 'max:3';
                }
            }
        } elseif ($field['type'] === 'date') {
            $base = ['nullable', 'date'];
        } elseif ($field['type'] === 'textarea' || $field['type'] === 'text') {
            $base = ['nullable', 'string', 'max:65535'];
        }

        if (! empty($base)) {
            $rules[$key] = $required ? ['required', ...$base] : $base;
        }
    }

    return $rules;
}

    /**
     * Build blotter payload from validated request and form config.
     */
    private function buildPayloadFromValidated(array $form, array $validated): array
    {
        $formId = $validated['form_number'];
        $formLabel = $form['label'] ?? 'KP Form No. '.$formId;

        $complainantId = null;
        $respondentId = null;
        $formData = [];
        $primaryDate = null;
        $purpose = $form['purpose'] ?? '';

        $residentSingle = ['appointee_id', 'member_id', 'complainant_id', 'respondent_id'];
        $residentMulti = ['member_ids', 'respondent_ids', 'witness_ids', 'resident_ids', 'complainant_ids'];
        $dateFields = [
            'notice_date', 'oath_date', 'record_date', 'removal_date', 'incident_date', 'hearing_date',
            'form_date', 'selection_date', 'settlement_date', 'decision_date', 'rejection_date',
            'certification_date', 'confrontation_date', 'settlement_rejected_date', 'mediation_failed_date',
            'conciliation_failed_date', 'respondent_failed_date', 'failure_at_punong_date', 'pangkat_meeting_date',
            'respondent_failed_pangkat_date', 'complainant_failed_date', 'motion_date', 'execution_date',
            'dismissal_date', 'proceeding_date', 'deadline_date',
        ];

        foreach ($form['fields'] ?? [] as $field) {
            $name = $field['name'];
            $value = $validated[$name] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($name, $residentSingle, true)) {
                if ($name === 'complainant_id') {
                    $complainantId = $value;
                } elseif ($name === 'respondent_id') {
                    $respondentId = $value;
                } else {
                    $formData[$name] = $value;
                }

                continue;
            }

            if (in_array($name, $residentMulti, true)) {
                $formData[$name] = is_array($value) ? $value : [];

                continue;
            }

            if (in_array($name, $dateFields, true)) {
                $formData[$name] = $value;
                if ($primaryDate === null && $value) {
                    $primaryDate = $value;
                }

                continue;
            }

            $formData[$name] = $value;
        }

        if ($complainantId === null && !empty($formData['complainant_ids'])) {
    $complainantId = $formData['complainant_ids'][0];
}

if ($respondentId === null && !empty($formData['respondent_ids'])) {
    $respondentId = $formData['respondent_ids'][0];
}


        $incidentDate = $primaryDate ? (\Illuminate\Support\Carbon::parse($primaryDate)->format('Y-m-d')) : now()->format('Y-m-d');

        return [
            'form_number' => $formLabel,
            'form_id' => $formId,
            'complainant_id' => $complainantId,
            'respondent_id' => $respondentId,
            'incident_type' => $validated['incident_type'] ?? null,
            'purpose' => $purpose,
            'incident_details' => $validated['incident_details'] ?? null,
            'incident_date' => $incidentDate,
            'status' => $validated['status'],
            'form_data' => $formData,
        ];
    }

    private function resolveFormDataForDisplay(BlotterRecord $blotter): array
    {
        $formData = $blotter->form_data ?? [];
        $residentKeys = ['appointee_id', 'member_id', 'member_ids', 'respondent_ids', 'witness_ids', 'resident_ids', 'complainant_ids'];
        $collectIds = fn ($v) => is_array($v) ? $v : [$v];
        $ids = array_unique(array_filter(array_merge(
            [$blotter->complainant_id, $blotter->respondent_id],
            $collectIds($formData['appointee_id'] ?? []),
            $collectIds($formData['member_id'] ?? []),
            $formData['member_ids'] ?? [],
            $formData['respondent_ids'] ?? [],
            $formData['witness_ids'] ?? [],
            $formData['resident_ids'] ?? [],
            $formData['complainant_ids'] ?? []
        )));
       $residents = Resident::whereIn('id', $ids)->get()->keyBy('id');

        $out = [];
        foreach ($formData as $key => $value) {
            if (in_array($key, ['complainant_id', 'respondent_id'], true)) {
                continue;
            }
            if (in_array($key, $residentKeys, true)) {
                if (is_array($value)) {
                    $value = implode(', ', array_map(fn ($id) => $residents->get($id)?->full_name ?? $id, $value));
                } else {
                    $value = $residents->get($value)?->full_name ?? $value;
                }
            }
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($value !== null && $value !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                    try {
                        $value = \Carbon\Carbon::parse($value)->format('F j, Y');
                    } catch (\Throwable $e) {
                        // keep as-is
                    }
                }
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function docxFilename(BlotterRecord $blotter): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $blotter->form_number);

        return $safe.'_'.$blotter->blotter_id.'.docx';
    }

    /**
     * Generate DOCX for the blotter. Uses KpFormDocxExporter (certificate-style):
     * templates from storage/app/private/public/blotter_docs or
     * resources/views/certificates/templates; per-form kp_formN.docx or
     * page-by-page from kp_form.docx; write to temp then copy to storage.
     * Falls back to PhpWord-generated docx if template export fails.
     */
    private function generateDocx(BlotterRecord $blotter): void
    {
        Storage::makeDirectory('public/blotter_docs');
        $path = storage_path('app/public/blotter_docs/'.$this->docxFilename($blotter));
        @mkdir(dirname($path), 0777, true);

        try {
            $exporter = new KpFormDocxExporter;
            if ($exporter->exportToFile($blotter, $path)) {
                Log::info('Successfully exported template to: '.$path);

                return;
            } else {
                Log::warning('Template export failed - template file may not exist');
                // If template export fails, create a simple document indicating the issue
                $phpWord = new PhpWord;
                $section = $phpWord->addSection();
                $section->addText('Template file kp_form.docx not found or export failed. Contact admin.', ['bold' => true]);

                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($path);

                return;
            }
        } catch (\Throwable $e) {
            Log::error('KP template export exception: '.$e->getMessage());
            // In case of exception, create a simple document indicating the error
            $phpWord = new PhpWord;
            $section = $phpWord->addSection();
            $section->addText('Error in template export: '.$e->getMessage(), ['bold' => true]);

            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($path);

            return;
        }
    }
}
