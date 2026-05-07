<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\Resident;
use App\Models\Purok;
use App\Models\IncidentType;
use App\Models\CertificateType;
use App\Services\KpFormDocxExporter;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use App\Models\CertificateRequest;

class Resident_viewsController extends Controller
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

    
    public function Blotter_index(\Illuminate\Http\Request $request)
    {
        $status = $request->input('status');
        $form   = $request->input('form');
        $sort   = $request->input('sort', 'priority');
        $search = $request->input('search');

        $query = BlotterRecord::query();

        // if (!auth()->user()->hasRole('Admin', 'Secretary')) {
        //     $query->where('recorded_by', auth()->user()->user_id);
        // }
         $query->where('recorded_by', auth()->user()->user_id);

          // ✅ Residents only see Approved and Released records
    $query->whereIn('status', ['Approved', 'Released', 'Pending']);

    // Status sub-filter (only allow Approved/Released to prevent URL tampering)
    if ($status && in_array($status, ['Approved', 'Released'])) {
        $query->where('status', $status);
    }

        if ($status) {
            $query->where('status', $status);
        }

        if ($form) {
    $query->where('form_number', $form);
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

        $records = $query->paginate(10)->withQueryString();

    // All possible resident ID field names across all KP forms
    $residentFields = [
        'complainant_id', 'complainant_ids',
        'respondent_id',  'respondent_ids',
        'appointee_id',
        'member_id',      'member_ids',
        'witness_ids',
        'resident_ids',
        'respondent_id_1', 'respondent_id_2',
    ];

    // Collect all IDs from any resident field in form_data
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
    ->keyBy('id');

    $records->each(function ($record) use ($residents, $residentFields) {
        $formData = $record->form_data ?? [];

        // Complainant: check dedicated column first, then form_data
        $cIds = $record->getRawOriginal('complainant_id')
            ? [$record->getRawOriginal('complainant_id')]
            : array_values(array_filter((array) ($formData['complainant_ids'] ?? $formData['complainant_id'] ?? [])));

        // Respondent: check dedicated column first, then form_data
        $rIds = $record->getRawOriginal('respondent_id')
            ? [$record->getRawOriginal('respondent_id')]
            : array_values(array_filter((array) ($formData['respondent_ids'] ?? $formData['respondent_id'] ?? [])));

        // For forms that don't have complainant/respondent, fall back to other party fields
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


        $kpForms = $this->kpForms();
        $filters = compact('status', 'form', 'sort', 'search');

        return view('blotter.resident_index', compact('records', 'kpForms', 'filters'));
    }

public function Blotter_show(BlotterRecord $blotter)
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

    return view('blotter.resident_show', compact('blotter', 'form', 'formDataResolved', 'locationPurok', 'locationArea'));
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
 

        return view('blotter.resident_create', compact(
    'kpForms', 'isPendingOnly', 'oldResidents', 'puroks', 'purokAreas', 'incidentTypes'
))->with('existingPurok', null);
    }

    public function searchResidents(\Illuminate\Http\Request $request)
    {
        $q = trim($request->input('q', ''));
    $query = \App\Models\Resident::query();  // no select — accessor handles last_name
if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('surname', 'like', $q . '%')           // real column
            ->orWhere('first_name', 'like', $q . '%')
            ->orWhereRaw("CONCAT(surname, ', ', first_name) LIKE ?", ['%' . $q . '%']); // real column
    });
}
$results = $query->orderBy('surname')->orderBy('first_name')->limit(30)->get()  // real column
    ->map(fn($r) => [
        'id'   => $r->id,               // new PK
        'text' => $r->last_name . ', ' . $r->first_name   // accessor works here
            . ($r->middle_name ? ' ' . substr($r->middle_name, 0, 1) . '.' : '')
            . ($r->suffix ? ' ' . $r->suffix : ''),
    ]);
        return response()->json($results);
    }


    
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
 
    return redirect()->route($indexRoute)
        ->with('success', 'Blotter record created successfully.');
}

    public function resident_show(BlotterRecord $blotter)
    {
        $blotter->load(['complainant', 'respondent', 'recordedBy']);
        $form = $this->getFormById($blotter->form_id ?? $blotter->form_number);
        $formDataResolved = $this->resolveFormDataForDisplay($blotter);
        return view('blotter.show', compact('blotter', 'form', 'formDataResolved'));
    }

    public function resident_edit(BlotterRecord $blotter)
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

        return view('blotter.resident_edit',compact(
    'blotter', 'residents', 'kpForms', 'form',
    'puroks', 'purokAreas', 'incidentTypes', 'selectedTypeIds'
))->with('existingPurok', $existingPurok);
    }


public function resident_update(Request $request, BlotterRecord $blotter)
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



    return redirect()->route('blotter.resident_index')
        ->with('success', 'Blotter record updated successfully.');
}

    
public function Certificates_index(Request $request)
{
    $statusFilter = $request->input('status', 'All');
    $typeFilter   = $request->input('type', 'All');
    $search       = $request->input('search');

    $requests = CertificateRequest::with(['resident', 'certificateType'])
        // ✅ Only the current user's own requests
        ->where('requested_by', auth()->user()->name)
        // ✅ Only Pending, Approved, Released
        ->whereIn('status', ['Pending', 'Approved', 'Released'])
        // ✅ Optional sub-filter (validated against allowed statuses)
        ->when(
            $statusFilter && $statusFilter !== 'All' && in_array($statusFilter, ['Pending', 'Approved', 'Released']),
            function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            }
        )
        ->when($typeFilter && $typeFilter !== 'All', function ($query) use ($typeFilter) {
            $query->whereHas('certificateType', function ($q) use ($typeFilter) {
                $q->where('certificate_name', $typeFilter);
            });
        })
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('resident', function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%$search%")
                      ->orWhere('surname', 'like', "%$search%");  
                })
                ->orWhere('purpose', 'like', "%$search%")
                ->orWhereHas('certificateType', function ($q2) use ($search) {
                    $q2->where('certificate_name', 'like', "%$search%");
                });
            });
        })
        ->latest()
        ->paginate(10);

    $certificateTypes = CertificateType::orderBy('certificate_name')->pluck('certificate_name');

    return view('certificates.resident_index', compact('requests', 'statusFilter', 'typeFilter', 'certificateTypes'));
}

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Safely decode evidence_pic regardless of how it was stored.
     * Handles: null, JSON string, already-decoded array, escaped JSON.
     */
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

        if ($field['type'] === 'resident') {
            $base = ['nullable', 'exists:profiling-system.residents,id'];  // ← fixed
        } elseif ($field['type'] === 'resident_multi') {
            $base = ['nullable', 'array'];
            $rules[$key.'.*'] = 'nullable|exists:profiling-system.residents,id';  // ← fixed
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
}