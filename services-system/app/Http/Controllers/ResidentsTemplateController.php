<?php

namespace App\Http\Controllers;

use App\Imports\ResidentsImport;
use App\Models\ResidentsImportTemp;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResidentsTemplateController extends Controller
{
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Simple format based on user's actual data structure
        // household_no, lastname, firstname, middlename, gender, bday, age, address, civil_status, role
        $headers = [
            'household_no',
            'lastname',
            'firstname',
            'middlename',
            'gender',
            'bday',
            'age',
            'address',
            'civil_status',
            'role',
        ];

        // Add headers to Row 1
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.'1', $header);
            $column++;
        }

        // Style the header row
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E2E8F0',
                ],
            ],
        ]);

        // Add sample data rows based on user's actual data
        $sampleRows = [
            [
                '1',                                    // household_no
                'Wahing',                               // lastname
                'Eleonora',                             // firstname
                'Torralba',                             // middlename
                'F',                                    // gender
                '03/08/1968',                          // bday
                '57',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Head',                                  // role
            ],
            [
                '',                                     // household_no (empty - same household)
                'Valenzuela',                           // lastname
                'Santina',                              // firstname
                '',                                     // middlename
                'F',                                    // gender
                '23/12/2009',                          // bday
                '16',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Niece',                                 // role
            ],
            [
                '',                                     // household_no
                'Wahing',                               // lastname
                'Edwin',                                // firstname
                'Jr.',                                  // middlename
                'M',                                    // gender
                '22/04/1988',                          // bday
                '37',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '',                                     // household_no
                'Alejo',                                // lastname
                'Joel',                                 // firstname
                'Bayor',                                // middlename
                'M',                                    // gender
                '26/07/1981',                          // bday
                '44',                                   // age
                'Bulacan',                              // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '',                                     // household_no
                'Alejo',                                // lastname
                'Cherryl',                              // firstname
                'Bayor',                                // middlename
                'F',                                    // gender
                '09/02/1984',                          // bday
                '41',                                   // age
                'Bulacan',                              // address
                'Single',                               // civil_status
                'Daughter',                              // role
            ],
            [
                '',                                     // household_no
                'De La Cruz',                           // lastname
                'Keith',                                // firstname
                'Jervahn',                              // middlename
                'M',                                    // gender
                '16/08/2011',                          // bday
                '14',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '',                                     // household_no
                'De La Cruz',                           // lastname
                'Beyonce',                              // firstname
                '',                                     // middlename
                'F',                                    // gender
                '23/12/2014',                          // bday
                '11',                                   // age
                'Cabadbaran',                           // address
                'Single',                               // civil_status
                'Daughter',                              // role
            ],
            [
                '',                                     // household_no
                'De La Cruz',                           // lastname
                'Breezy',                               // firstname
                '',                                     // middlename
                'M',                                    // gender
                '29/03/2019',                          // bday
                '6',                                    // age
                'Butuan',                               // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '2',                                    // household_no (new household)
                'De Guzman',                            // lastname
                'Jorben',                               // firstname
                '',                                     // middlename
                'M',                                    // gender
                '28/02/1982',                          // bday
                '43',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'Joristhideles',                        // firstname
                '',                                     // middlename
                'F',                                    // gender
                '31/12/2016',                          // bday
                '9',                                    // age
                'Cabadbaran',                           // address
                'Single',                               // civil_status
                'Grand daughter',                        // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'Jane',                                 // firstname
                'Pontillas',                            // middlename
                'F',                                    // gender
                '28/02/1985',                          // bday
                '40',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Daughter',                              // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'El Jane',                              // firstname
                '',                                     // middlename
                'F',                                    // gender
                '10/08/2022',                          // bday
                '3',                                    // age
                'Butuan',                               // address
                'Single',                               // civil_status
                'Grand daughter',                        // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'Shieckeena',                           // firstname
                '',                                     // middlename
                'F',                                    // gender
                '13/03/2024',                          // bday
                '1',                                    // age
                '',                                     // address (empty)
                'Single',                               // civil_status
                'Grand daughter',                        // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'James',                                // firstname
                'Odsigue',                              // middlename
                'M',                                    // gender
                '22/04/2008',                          // bday
                '17',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Son',                                   // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'Jasmin',                               // firstname
                'Odsigue',                              // middlename
                'F',                                    // gender
                '08/07/2009',                          // bday
                '16',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Daughter',                              // role
            ],
            [
                '',                                     // household_no
                'De Guzman',                            // lastname
                'Jelyn',                                // firstname
                'Odsigue',                              // middlename
                'F',                                    // gender
                '21/10/2013',                          // bday
                '12',                                   // age
                'Magallanes',                           // address
                'Single',                               // civil_status
                'Daughter',                              // role
            ],
        ];

        // Add sample data starting from Row 2
        $row = 2;
        foreach ($sampleRows as $sampleData) {
            $column = 'A';
            foreach ($sampleData as $data) {
                $sheet->setCellValue($column.$row, $data);
                $column++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'residents_import_template.xlsx';

        ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function ajaxUpload(Request $request)
    {
        try {
            // Validate the file
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);

            // Clear any existing temporary records before import
            ResidentsImportTemp::truncate();

            // Process the file upload
            Excel::import(new ResidentsImport($request->file('file')->getClientOriginalName()), $request->file('file'));

            // Get counts
            $importedCount = ResidentsImportTemp::count();
            $validRecords = ResidentsImportTemp::whereRaw('LOWER(import_status) = ?', ['valid'])->orderBy('temp_id')->get();
            $invalidCount = ResidentsImportTemp::whereRaw('LOWER(import_status) = ?', ['invalid'])->count();

            // Auto-commit valid records to residents table
            $successCount = 0;
            $committedIds = [];
            $failed = [];

            foreach ($validRecords as $record) {
                try {
                    $firstName = trim($record->first_name_raw ?? '');
                    $lastName = trim($record->last_name_raw ?? '');

                    if ($firstName === '') {
                        $firstName = 'N/A';
                    }
                    if ($lastName === '') {
                        $lastName = 'N/A';
                    }

                    $purokName = $this->normalizePurokName($record->purok_raw ?? null);
                    $purok = \App\Models\Purok::firstOrCreate(['purok_name' => $purokName]);

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

                    \Illuminate\Support\Facades\DB::transaction(function () use ($residentData, $record, &$committedIds, &$successCount) {
                        $resident = \App\Models\Resident::create($residentData);
                        $committedIds[] = $resident->resident_id;
                        $record->delete();
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
                        // swallow
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$importedCount} rows. Committed {$successCount} valid to residents. {$invalidCount} invalid left for editing.",
                'count' => $importedCount,
                'committed' => $successCount,
                'invalid_remaining' => $invalidCount,
                'committed_ids' => $committedIds,
                'failed_records' => $failed,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: '.$e->getMessage(),
            ], 422);
        }
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
