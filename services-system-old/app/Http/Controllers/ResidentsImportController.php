<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Resident;
use App\Models\ResidentsImportTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResidentsImportController extends Controller
{
    public function commitAll(Request $request)
    {
        $before = [
            'temp_total' => ResidentsImportTemp::count(),
            'temp_valid' => ResidentsImportTemp::whereRaw('LOWER(import_status) = ?', ['valid'])->count(),
            'residents_total' => Resident::count(),
        ];

        $validRecords = ResidentsImportTemp::orderBy('temp_id')->get();

        $totalValid = $validRecords->count();
        if ($totalValid === 0) {
            return response()->json([
                'message' => 'No records to commit.',
                'before' => $before,
            ], 200);
        }

        $successCount = 0;
        $failed = [];
        $committedIds = [];
        $tempIdsToDelete = [];

        foreach ($validRecords as $record) {
            try {
                // Ignore empty fields: even if first_name, last_name, or any other field is empty, still insert the record.
                $firstName = trim($record->first_name_raw ?? '');
                $lastName = trim($record->last_name_raw ?? '');

                // Use defaults to ensure database insertion succeeds (as columns might be not null)
                // but do not skip the record.
                if ($firstName === '') {
                    $firstName = 'N/A';
                }
                if ($lastName === '') {
                    $lastName = 'N/A';
                }

                $purokName = $this->normalizePurokName($record->purok_raw ?? null);
                $purok = Purok::firstOrCreate(['purok_name' => $purokName]);

                $birthDate = $record->birth_date_raw;
                if (! $birthDate && $record->age) {
                    $birthDate = now()->subYears($record->age);
                }
                if (! $birthDate && ! empty($record->age_raw)) {
                    $age = $this->parseAgeValue($record->age_raw);
                    if ($age > 0) {
                        $birthDate = now()->subYears($age);
                    }
                }

                $sex = 'Male';
                if (! empty($record->sex_raw)) {
                    $v = strtoupper(trim($record->sex_raw));
                    if ($v === 'M') {
                        $sex = 'Male';
                    } elseif ($v === 'F') {
                        $sex = 'Female';
                    } elseif (in_array(ucfirst(strtolower(trim($record->sex_raw))), ['Male', 'Female'])) {
                        $sex = ucfirst(strtolower(trim($record->sex_raw)));
                    }
                }

                $civilStatus = $this->parseCivilStatusValue($record->civil_status_raw) ?? 'Single';

                $residentData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => trim($record->middle_name_raw ?? '') ?: null,
                    'suffix' => trim($record->suffix_raw ?? '') ?: null,
                    'birth_date' => $birthDate,
                    'birth_place' => $record->birth_place_raw,
                    'address' => (trim($record->address_raw ?? '') !== '' ? trim($record->address_raw) : 'Unknown'),
                    'household_number' => $record->household_number_raw ?? null,
                    'educational_attainment' => $record->educational_attainment_raw,
                    'grade_course' => $record->grade_course_raw,
                    'school' => $record->school_raw,
                    'profession_occupation' => $record->profession_occupation_raw,
                    'employment_type' => $record->employment_type_raw,
                    'relation_to_head' => $record->relation_to_head_raw,
                    'purok_id' => $purok->purok_id,
                    'sex' => $sex,
                    'civil_status' => $civilStatus,
                    'contact_number' => $record->contact_number_raw,
                    'fb_email_address' => $record->fb_email_address_raw,
                    'phic_no' => $record->phic_no_raw,
                    'membership' => $record->membership_raw,
                    'family_planning_method' => $record->family_planning_method_raw,
                    'sanitary_toilet' => $record->sanitary_toilet_raw,
                    'water_supply' => ($record->water_supply_raw ?? 'I'),
                    'smoker' => $record->smoker_raw,
                    'binge_drinker' => $record->binge_drinker_raw,
                    'hpn' => $record->hpn_raw,
                    'dm' => $record->dm_raw,
                    'pwd' => $record->pwd_raw ?? false,
                    'pwd_type' => $record->pwd_type_raw,
                    'mothers_maiden_name' => $record->mothers_maiden_name_raw,
                    'date_registered' => now(),
                    'residency_status' => 'Active',
                ];

                DB::transaction(function () use ($residentData, $record, &$tempIdsToDelete, &$successCount, &$committedIds) {
                    $resident = Resident::create($residentData);
                    $committedIds[] = $resident->resident_id;
                    $tempIdsToDelete[] = $record->temp_id;
                    $successCount++;
                });
            } catch (\Throwable $e) {
                $failed[] = [
                    'temp_id' => $record->temp_id,
                    'error' => $e->getMessage(),
                ];
                try {
                    $record->update([
                        'import_status' => $record->import_status ?? 'INVALID',
                        'import_error_message' => $e->getMessage(),
                    ]);
                } catch (\Throwable $u) {
                    Log::warning('Failed to annotate temp record error', ['temp_id' => $record->temp_id, 'error' => $u->getMessage()]);
                }
            }
        }

        if (! empty($tempIdsToDelete)) {
            ResidentsImportTemp::whereIn('temp_id', $tempIdsToDelete)->delete();
        }

        $after = [
            'temp_total' => ResidentsImportTemp::count(),
            'temp_valid' => ResidentsImportTemp::whereRaw('LOWER(import_status) = ?', ['valid'])->count(),
            'residents_total' => Resident::count(),
        ];

        return response()->json([
            'message' => 'Commit complete',
            'summary' => [
                'total_valid' => $totalValid,
                'successful' => $successCount,
                'failed' => count($failed),
            ],
            'before' => $before,
            'after' => $after,
            'committed_ids' => $committedIds,
            'failed_records' => $failed,
        ], 200);
    }

    private function parseAgeValue($value): int
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        if (preg_match('/\d+/', (string) $value, $m)) {
            return (int) $m[0];
        }

        return 0;
    }

    private function parseCivilStatusValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $v = strtoupper(trim((string) $value));
        $map = [
            'SINGLE' => 'Single',
            'MARRIED' => 'Married',
            'WIDOWED' => 'Widowed',
            'WIDOWER' => 'Widowed',
            'SEPARATED' => 'Separated',
            'LIVEIN' => 'Live-in',
            'LIVE-IN' => 'Live-in',
            'ANNULLED' => 'Annulled',
        ];
        if (isset($map[$v])) {
            return $map[$v];
        }

        return ucfirst(strtolower(trim((string) $value)));
    }

    private function normalizePurokName($value): string
    {
        $raw = (string) $value;
        $s = strtolower(trim($raw));
        if ($s === '' || $s === 'default') {
            return 'Default';
        }
        $s = preg_replace('/\bpurok\b/i', '', $s);
        $s = preg_replace('/[^a-z0-9]/i', '', $s);
        if ($s === '') {
            return 'Default';
        }
        if (preg_match('/^0*([0-9]+)([a-z]*)$/i', $s, $m)) {
            $num = (int) $m[1];
            $suffix = strtoupper($m[2] ?? '');
            return 'Purok '.$num.$suffix;
        }
        return 'Purok '.ucwords(trim($raw));
    }
}
