<?php

namespace App\Livewire\Residents;

use App\Imports\ResidentsImport;
use App\Models\Purok;
use App\Models\Resident;
use App\Models\ResidentsImportTemp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Import extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $file;

    public $editingRecordId = null;

    public $editingData = [];

    public $confirmCommitId = null;

    public function upload()
    {
        Log::info('=== UPLOAD METHOD CALLED ===', [
            'file_name' => $this->file ? $this->file->getClientOriginalName() : 'NO FILE',
        ]);

        // Check if file exists
        if (! $this->file) {
            session()->flash('error', 'Please select a file to upload.');

            return;
        }

        // Validate the file first
        $this->validate();

        try {
            // Clear any existing temporary records before import
            ResidentsImportTemp::truncate();
            Log::info('Cleared existing temp records');

            // Excel::import handles uploaded files directly
            Excel::import(new ResidentsImport($this->file->getClientOriginalName()), $this->file);

            // Get count of imported records
            $importedCount = ResidentsImportTemp::count();
            $validCount = ResidentsImportTemp::where('import_status', 'VALID')->count();
            $invalidCount = ResidentsImportTemp::where('import_status', 'INVALID')->count();

            Log::info('File import completed', [
                'total_records' => $importedCount,
                'valid_records' => $validCount,
                'invalid_records' => $invalidCount,
            ]);

            session()->flash('message', "File imported successfully. {$importedCount} records ready for review ({$validCount} valid, {$invalidCount} invalid).");

            $this->reset('file');

            // Refresh the component to show the newly imported records
            $this->dispatch('import-completed');
        } catch (\Exception $e) {
            Log::error('File upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Error importing file: '.$e->getMessage());
        }
    }

    public function editRecord($id)
    {
        $record = ResidentsImportTemp::find($id);
        if ($record) {
            $this->editingRecordId = $id;
            // Set all the fields for editing using the same structure as the Residents Edit component
            $this->editingData = [
                'first_name_raw' => $record->first_name_raw,
                'middle_name_raw' => $record->middle_name_raw,
                'last_name_raw' => $record->last_name_raw,
                'suffix_raw' => $record->suffix_raw,
                'age_raw' => $record->age_raw,
                'birth_date_raw' => $record->birth_date_raw ? $record->birth_date_raw->format('Y-m-d') : null,
                'birth_place_raw' => $record->birth_place_raw,
                'sex_raw' => $record->sex_raw,
                'civil_status_raw' => $record->civil_status_raw,
                'relation_to_head_raw' => $record->relation_to_head_raw,
                'address_raw' => $record->address_raw,
                'purok_raw' => $record->purok_raw,
                'contact_number_raw' => $record->contact_number_raw,
                'fb_email_address_raw' => $record->fb_email_address_raw,
                'phic_no_raw' => $record->phic_no_raw,
                'membership_raw' => $record->membership_raw,
                'family_planning_method_raw' => $record->family_planning_method_raw,
                'sanitary_toilet_raw' => $record->sanitary_toilet_raw,
                'water_supply_raw' => $record->water_supply_raw,
                'smoker_raw' => $record->smoker_raw,
                'binge_drinker_raw' => $record->binge_drinker_raw,
                'hpn_raw' => $record->hpn_raw,
                'dm_raw' => $record->dm_raw,
                'pwd_raw' => $record->pwd_raw,
                'pwd_type_raw' => $record->pwd_type_raw,
                'mothers_maiden_name_raw' => $record->mothers_maiden_name_raw,
                'date_registered' => $record->date_registered ? $record->date_registered->format('Y-m-d') : now()->format('Y-m-d'),
                'household_number_raw' => $record->household_number_raw,
                'educational_attainment_raw' => $record->educational_attainment_raw,
                'grade_course_raw' => $record->grade_course_raw,
                'school_raw' => $record->school_raw,
                'profession_occupation_raw' => $record->profession_occupation_raw,
                'employment_type_raw' => $record->employment_type_raw,
            ];
            $boolKeys = ['sanitary_toilet_raw', 'smoker_raw', 'binge_drinker_raw', 'hpn_raw', 'dm_raw', 'pwd_raw'];
            foreach ($boolKeys as $k) {
                $val = $record->$k;
                $this->editingData[$k] = is_null($val) ? '' : ($val ? '1' : '0');
            }
        }
    }

    public function cancelEdit()
    {
        $this->editingRecordId = null;
        $this->editingData = [];
    }

    public function deleteEditingRecord()
    {
        if (! $this->editingRecordId) {
            return;
        }
        $record = ResidentsImportTemp::find($this->editingRecordId);
        if ($record) {
            $record->delete();
            session()->flash('message', 'Record deleted successfully.');
        }
        $this->cancelEdit();
    }

    public function saveEdit()
    {
        // Sanitize PWD Type based on PWD value
        if (empty($this->editingData['pwd_raw'])) {
            $this->editingData['pwd_type_raw'] = null;
        }

        if (! empty($this->editingData['pwd_raw']) && empty($this->editingData['pwd_type_raw'])) {
            $this->addError('editingData.pwd_type_raw', 'PWD Type is required when PWD is Yes.');

            return;
        }

        // Normalize boolean-like fields to 1/0/null to ensure consistent saving
        $boolKeys = ['sanitary_toilet_raw', 'smoker_raw', 'binge_drinker_raw', 'hpn_raw', 'dm_raw', 'pwd_raw'];
        foreach ($boolKeys as $k) {
            $this->editingData[$k] = $this->normalizeBool($this->editingData[$k] ?? null);
        }

        if ($this->editingRecordId) {
            $record = ResidentsImportTemp::find($this->editingRecordId);
            if ($record) {
                $computedAge = 0;
                if (! empty($this->editingData['birth_date_raw'])) {
                    try {
                        $bd = \Carbon\Carbon::parse($this->editingData['birth_date_raw']);
                        $computedAge = max(0, min(120, (int) $bd->age));
                    } catch (\Throwable $e) {
                        $computedAge = 0;
                    }
                }
                if ($computedAge === 0 && isset($this->editingData['age_raw'])) {
                    $parsedAge = $this->parseAgeValue($this->editingData['age_raw']);
                    $computedAge = max(0, min(120, (int) $parsedAge));
                }
                $record->update([
                    'first_name_raw' => $this->editingData['first_name_raw'],
                    'middle_name_raw' => $this->editingData['middle_name_raw'],
                    'last_name_raw' => $this->editingData['last_name_raw'],
                    'suffix_raw' => $this->editingData['suffix_raw'],
                    'age_raw' => $this->editingData['age_raw'],
                    'age' => $computedAge ?: null,
                    'birth_date_raw' => $this->editingData['birth_date_raw'],
                    'birth_place_raw' => $this->editingData['birth_place_raw'],
                    'sex_raw' => $this->editingData['sex_raw'],
                    'civil_status_raw' => $this->editingData['civil_status_raw'],
                    'relation_to_head_raw' => $this->editingData['relation_to_head_raw'],
                    'address_raw' => $this->editingData['address_raw'],
                    'purok_raw' => $this->editingData['purok_raw'],
                    'contact_number_raw' => $this->editingData['contact_number_raw'],
                    'fb_email_address_raw' => $this->editingData['fb_email_address_raw'],
                    'phic_no_raw' => $this->editingData['phic_no_raw'],
                    'membership_raw' => $this->editingData['membership_raw'],
                    'family_planning_method_raw' => $this->editingData['family_planning_method_raw'],
                    'sanitary_toilet_raw' => $this->editingData['sanitary_toilet_raw'],
                    'water_supply_raw' => $this->editingData['water_supply_raw'],
                    'smoker_raw' => $this->editingData['smoker_raw'],
                    'binge_drinker_raw' => $this->editingData['binge_drinker_raw'],
                    'hpn_raw' => $this->editingData['hpn_raw'],
                    'dm_raw' => $this->editingData['dm_raw'],
                    'pwd_raw' => $this->editingData['pwd_raw'],
                    'pwd_type_raw' => $this->editingData['pwd_type_raw'],
                    'mothers_maiden_name_raw' => $this->editingData['mothers_maiden_name_raw'],
                    'date_registered' => $this->editingData['date_registered'],
                    'household_number_raw' => $this->editingData['household_number_raw'],
                    'educational_attainment_raw' => $this->editingData['educational_attainment_raw'],
                    'grade_course_raw' => $this->editingData['grade_course_raw'],
                    'school_raw' => $this->editingData['school_raw'],
                    'profession_occupation_raw' => $this->editingData['profession_occupation_raw'],
                    'employment_type_raw' => $this->editingData['employment_type_raw'],
                ]);

                $this->editingRecordId = null;
                $this->editingData = [];
                session()->flash('message', 'Record updated successfully.');
            }
        }
    }

    public function commit()
    {
        $records = ResidentsImportTemp::whereRaw('LOWER(import_status) = ?', ['valid'])->orderBy('temp_id')->get();
        if ($records->isEmpty()) {
            session()->flash('error', 'No valid staged records to commit.');
            return;
        }
        $success = 0;
        foreach ($records as $record) {
            if ($record->pwd_raw == 1 && empty($record->pwd_type_raw)) {
                try {
                    $record->update([
                        'import_status' => 'INVALID',
                    ]);
                } catch (\Throwable $e) {
                }
                continue;
            }
            try {
                $firstName = $record->first_name_raw;
                $lastName = $record->last_name_raw;
                $middleName = $record->middle_name_raw;
                $suffix = $record->suffix_raw;
                if ((empty($firstName) || empty($lastName)) && ! empty($record->full_name_raw)) {
                    [$firstName, $middleName, $lastName, $suffix] = $this->parseFullName($record->full_name_raw);
                }
                if (empty($firstName) && ! empty($lastName)) {
                    $firstName = 'N/A';
                } elseif (empty($lastName) && ! empty($firstName)) {
                    $lastName = 'N/A';
                } elseif (empty($firstName) && empty($lastName)) {
                    $firstName = 'Unknown';
                    $lastName = 'Unknown';
                }
                $purokName = $this->normalizePurokName($record->purok_raw ?? null);
                $purok = Purok::firstOrCreate(['purok_name' => $purokName]);
                $birthDate = $record->birth_date_raw;
                $age = $record->age;
                if (! $birthDate && $age) {
                    $birthDate = now()->subYears($age);
                }
                if (! $birthDate && empty($age) && ! empty($record->age_raw)) {
                    $parsedAge = $this->parseAgeValue($record->age_raw);
                    if ($parsedAge > 0) {
                        $birthDate = now()->subYears($parsedAge);
                    } else {
                        $birthDate = $this->parseBirthDate($record->age_raw);
                    }
                }
                $sex = 'Male';
                if (! empty($record->sex_raw)) {
                    $parsedSex = ucfirst(strtolower(trim($record->sex_raw)));
                    if (in_array($parsedSex, ['Male', 'Female'])) {
                        $sex = $parsedSex;
                    } elseif (in_array(strtoupper($record->sex_raw), ['M', 'F'])) {
                        $sex = strtoupper($record->sex_raw) === 'M' ? 'Male' : 'Female';
                    }
                }
                $civilStatus = $this->parseCivilStatus($record->civil_status_raw) ?? 'Single';
                $address = $record->address_raw ?? 'Unknown';
                DB::transaction(function () use ($record, $firstName, $lastName, $middleName, $suffix, $purok, $birthDate, $sex, $civilStatus, $address) {
                    Resident::create([
                        'birth_date' => $birthDate,
                        'birth_place' => $record->birth_place_raw,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'suffix' => $suffix,
                        'address' => $address,
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
                        'water_supply' => $record->water_supply_raw ?? 'I',
                        'smoker' => $record->smoker_raw,
                        'binge_drinker' => $record->binge_drinker_raw,
                        'hpn' => $record->hpn_raw,
                        'dm' => $record->dm_raw,
                        'pwd' => $record->pwd_raw ?? false,
                        'pwd_type' => $record->pwd_type_raw,
                        'mothers_maiden_name' => $record->mothers_maiden_name_raw,
                        'date_registered' => now(),
                        'residency_status' => 'Active',
                    ]);
                    $record->delete();
                });
                $success++;
            } catch (\Throwable $e) {
                Log::error('Failed to commit resident: '.$e->getMessage(), [
                    'record_id' => $record->temp_id,
                ]);
                try {
                    $record->update([
                        'import_status' => $record->import_status ?? 'INVALID',
                        'import_error_message' => $e->getMessage(),
                    ]);
                } catch (\Throwable $u) {
                }
            }
        }
        session()->flash('message', "Successfully committed {$success} residents");
        $this->dispatch('import-completed');
    }

    public function commitIndividual($id)
    {
        $record = ResidentsImportTemp::find($id);
        if (! $record) {
            session()->flash('error', 'Record not found.');

            return;
        }

        // Validate PWD Requirement
        if ($record->pwd_raw == 1 && empty($record->pwd_type_raw)) {
            session()->flash('error', 'PWD Type is required when PWD is Yes. Please edit the record.');

            return;
        }

        try {
            // Use the separated name fields
            $firstName = $record->first_name_raw;
            $lastName = $record->last_name_raw;
            $middleName = $record->middle_name_raw;
            $suffix = $record->suffix_raw;

            // If separated name fields are not available, use fallback
            // At least one name must exist (checked during import), but we need both for database
            if (empty($firstName) && ! empty($lastName)) {
                // If only last name, use it as last name and set first name to "N/A"
                $firstName = 'N/A';
            } elseif (empty($lastName) && ! empty($firstName)) {
                // If only first name, use it as first name and set last name to "N/A"
                $lastName = 'N/A';
            } elseif (empty($firstName) && empty($lastName)) {
                // Fallback if both are empty (shouldn't happen for VALID records)
                $firstName = 'Unknown';
                $lastName = 'Unknown';
            }

            // Find or Create Purok - use default if not provided
            $purokName = $this->normalizePurokName($record->purok_raw ?? null);
            $purok = Purok::firstOrCreate(['purok_name' => $purokName]);

            // Use the new age field if available, otherwise calculate from birth_date
            $birthDate = $record->birth_date_raw;
            $age = $record->age;

            // If birth date is not available but age is, calculate birth date
            if (! $birthDate && $age) {
                $birthDate = now()->subYears($age);
            }

            // If neither birth date nor age is available, try to parse from age_raw field
            if (! $birthDate && empty($age) && ! empty($record->age_raw)) {
                $parsedAge = $this->parseAgeValue($record->age_raw);
                if ($parsedAge > 0) {
                    $birthDate = now()->subYears($parsedAge);
                } else {
                    $birthDate = $this->parseBirthDate($record->age_raw);
                }
            }

            // Parse sex with default fallback
            $sex = 'Male'; // Default
            if (! empty($record->sex_raw)) {
                $parsedSex = ucfirst(strtolower(trim($record->sex_raw)));
                if (in_array($parsedSex, ['Male', 'Female'])) {
                    $sex = $parsedSex;
                } elseif (in_array(strtoupper($record->sex_raw), ['M', 'F'])) {
                    $sex = strtoupper($record->sex_raw) === 'M' ? 'Male' : 'Female';
                }
            }

            // Parse civil status; allow null (not required)
            $civilStatus = $this->parseCivilStatus($record->civil_status_raw);

            // Get address with default fallback
            $address = $record->address_raw ?? 'Unknown';

            DB::transaction(function () use ($record, $firstName, $lastName, $middleName, $suffix, $purok, $birthDate, $sex, $civilStatus, $address) {
                Resident::create([
                    'birth_date' => $birthDate,
                    'birth_place' => $record->birth_place_raw,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'suffix' => $suffix,
                    'address' => $address,
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
                    'water_supply' => $record->water_supply_raw,  // Use the new consolidated field
                    'smoker' => $record->smoker_raw,
                    'binge_drinker' => $record->binge_drinker_raw,
                    'hpn' => $record->hpn_raw,
                    'dm' => $record->dm_raw,
                    'pwd' => $record->pwd_raw ?? false,
                    'pwd_type' => $record->pwd_type_raw,
                    'mothers_maiden_name' => $record->mothers_maiden_name_raw,
                    'date_registered' => now(),
                    'residency_status' => 'Active',
                ]);

                // Delete the record from temp table after successful commit
                $record->delete();
            });

            session()->flash('message', "Successfully committed resident: {$firstName} {$lastName}");
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to create resident from import: '.$e->getMessage(), [
                'record_id' => $record->temp_id,
                'full_name' => $record->last_name_raw.', '.$record->first_name_raw,
            ]);
            session()->flash('error', 'Failed to commit resident: '.$e->getMessage());
        }
    }


    private function normalizeBool($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = is_string($value) ? strtolower(trim($value)) : $value;
        if ($v === 1 || $v === true || $v === '1' || $v === 'y' || $v === 'yes' || $v === 'true' || $v === 'si' || $v === 'oo') {
            return 1;
        }
        if ($v === 0 || $v === false || $v === '0' || $v === 'n' || $v === 'no' || $v === 'false' || $v === 'hindi') {
            return 0;
        }

        return null;
    }

    private function parseDependentsStudying($value)
    {
        if (is_null($value) || $value === '') {
            return 0;
        }

        // Extract number from text like "2 students", "3 family members in school", etc.
        if (preg_match('/\d+/', $value, $matches)) {
            return (int) $matches[0];
        }

        // If it's just a number as string
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    private function parseAgeValue($value)
    {
        if (is_null($value) || $value === '' || stripos((string) $value, '#REF!') !== false) {
            return 0;
        }

        if (strpos((string) $value, '=DATEDIF') !== false) {
            if (preg_match('/\d+/', (string) $value, $matches)) {
                $age = (int) $matches[0];
                return ($age >= 0 && $age <= 120) ? $age : 0;
            }
            return 0;
        }

        if (preg_match('/(\d+)\\s*yrs?\\.?/i', (string) $value, $matches)) {
            $age = (int) $matches[1];
            return ($age >= 0 && $age <= 120) ? $age : 0;
        }

        // Extract number from text like "23 years", "23 yrs", "23 y/o", etc.
        if (preg_match('/\d+/', (string) $value, $matches)) {
            $age = (int) $matches[0];

            // Ensure age is reasonable (between 0 and 120)
            return ($age >= 0 && $age <= 120) ? $age : 0;
        }

        // If it's just a number as string
        if (is_numeric((string) $value)) {
            $age = (int) $value;

            return ($age >= 0 && $age <= 120) ? $age : 0;
        }

        return 0;
    }

    private function parseCivilStatus($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        $value = strtolower(trim($value));

        $validStatuses = [
            'single', 'married', 'widowed', 'divorced', 'separated',
        ];

        foreach ($validStatuses as $status) {
            if (strpos($value, $status) !== false) {
                return ucfirst($status);
            }
        }

        return null;
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

    private function parseFullName($value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [null, null, null, null];
        }
        if (strpos($value, ',') !== false) {
            $parts = array_map('trim', explode(',', $value, 2));
            $last = $parts[0];
            $rest = $parts[1] ?? '';
            $tokens = preg_split('/\s+/', $rest);
            $first = null;
            $middle = null;
            $suffix = null;
            if (count($tokens) >= 1) {
                $first = $tokens[0];
                if (count($tokens) >= 2) {
                    $middle = implode(' ', array_slice($tokens, 1));
                }
            }
            return [$first, $middle, $last, $suffix];
        }
        $tokens = preg_split('/\s+/', $value);
        $last = null;
        $first = null;
        $middle = null;
        $suffix = null;
        if (count($tokens) === 1) {
            $first = $tokens[0];
        } else {
            $last = array_pop($tokens);
            $first = implode(' ', $tokens);
        }
        return [$first ?: null, $middle, $last ?: null, $suffix];
    }

    private function parseBirthDate($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Skip common header values and obviously invalid dates
        $invalidValues = ['birth', 'birthday', 'bday', 'date', 'date_of_birth', 'birth_date'];
        if (in_array(strtolower(trim($value)), $invalidValues)) {
            return null;
        }

        // Skip all-uppercase text (likely headers)
        if (preg_match('/^[A-Z]+$/', trim($value))) {
            return null;
        }

        // Try to parse various date formats
        $formats = [
            'Y-m-d',      // 2024-01-15
            'm/d/Y',      // 01/15/2024
            'd/m/Y',      // 15/01/2024
            'm-d-Y',      // 01-15-2024
            'd-m-Y',      // 15-01-2024
            'Y.m.d',      // 2024.01.15
            'm.d.Y',      // 01.15.2024
            'd.m.Y',      // 15.01.2024
        ];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Exception $e) {
                // Continue to next format
                continue;
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.residents.import', [
            'tempRecords' => ResidentsImportTemp::latest('imported_at')->paginate(10),
            'puroks' => Purok::all(), // Add puroks to the view data
        ]);
    }

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
    ];

    public function updatedFile()
    {
        // This method is called when the file property is updated
        // We don't need to do anything specific here
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'file') {
            // Validate the file immediately when it's selected
            $this->validateOnly($propertyName);
        }
    }

    public function hydrate()
    {
        // Reset file property when component rehydrates to avoid file upload conflicts
        $this->reset('file');
    }
}
