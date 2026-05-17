<?php

namespace App\Imports;

use App\Models\ResidentsImportTemp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

// Set the heading row formatter to none to preserve original formatting
HeadingRowFormatter::default('none');

class ResidentsImport implements ToCollection, WithHeadingRow, WithStartRow
{
    protected $fileName;

    public function __construct($fileName = null)
    {
        $this->fileName = $fileName;
    }

    /**
     * Start reading from row 4 (rows 1-3 are headers and instructions)
     */
    public function startRow(): int
    {
        return 4;
    }

    /**
     * Use row 3 as the header row (system headers)
     */
    public function headingRow(): int
    {
        return 3;
    }

    /**
     * @param  Collection  $collection
     */
    public function collection(Collection $rows)
    {
        // Log the rows for debugging
        Log::info('Total rows to import: '.$rows->count());

        if ($rows->count() > 0) {
            Log::info('First row data:', $rows->first()->toArray());
        }

        // Determine which format we're dealing with
        $formatType = $this->detectFormat($rows);
        Log::info('Detected format type: '.$formatType);

        foreach ($rows as $index => $row) {
            // Skip empty rows
            if ($this->isRowEmpty($row)) {
                continue;
            }

            // Parse the row based on detected format
            $parsedData = $this->parseRowByFormat($row, $formatType, $index);

            // Log first few rows for debugging
            if ($index < 3) {
                Log::info("Parsed Row {$index} (Format: {$formatType}):", $parsedData);
            }

            Log::info("Row {$index} validation check", [
                'first_name_raw' => $parsedData['first_name_raw'] ?? null,
                'last_name_raw' => $parsedData['last_name_raw'] ?? null,
                'purok_raw' => $parsedData['purok_raw'] ?? null,
                'sex_raw' => $parsedData['sex_raw'] ?? null,
                'birth_date_raw' => $parsedData['birth_date_raw'] ?? null,
                'civil_status_raw' => $parsedData['civil_status_raw'] ?? null,
                'format_type' => $formatType,
            ]);

            // Validate the parsed data - make it lenient to accept records with just names
            $validator = $this->validateParsedData($parsedData, $formatType);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                Log::warning("Row {$index} validation failed", [
                    'errors' => $errors,
                    'parsed_data' => $parsedData,
                ]);
                // Store the record with validation error
                ResidentsImportTemp::create(array_merge($parsedData, [
                    'import_status' => 'INVALID',
                    'import_error_message' => $validator->errors()->first(),
                    'source_file' => $this->fileName,
                    'imported_at' => now(),
                ]));
            } else {
                Log::info("Row {$index} validation passed - marking as VALID", [
                    'first_name' => $parsedData['first_name_raw'] ?? null,
                    'last_name' => $parsedData['last_name_raw'] ?? null,
                ]);
                // Store the valid record - even if only names are provided
                ResidentsImportTemp::create(array_merge($parsedData, [
                    'import_status' => 'VALID',
                    'source_file' => $this->fileName,
                    'imported_at' => now(),
                ]));
            }
        }
    }

    private function detectFormat($rows)
    {
        if ($rows->count() == 0) {
            return 'unknown';
        }

        $firstRow = $rows->first()->toArray();
        // Convert to numeric array for consistent processing
        $firstRowValues = array_values($firstRow);

        // Check for NEW Format (updated template with descriptive headers)
        // This format has: NAME, LAST NAME, FIRST NAME, MIDDLE NAME, SEX, BIRTH DATE, etc.
        $newFormatIndicators = [
            'name', 'last name', 'first name', 'middle name', 'sex',
            'birth date', 'age', 'birth place', 'civil status', 'relation to head',
            'grade/course', 'school', 'profession / occupation', 'private', 'government',
            'contact no.', 'fb email', 'phic no.', 'membership',
            'family planning method', 'sanitary toilet', 'water supply level',
            'smoker', 'binge drinker', 'hpn', 'dm', 'mother\'s maiden name',
            'address', 'purok',
        ];
        $newFormatMatches = 0;
        foreach ($newFormatIndicators as $indicator) {
            foreach ($firstRowValues as $value) {
                $cleanedValue = strtolower(trim(strval($value)));
                if (stripos($cleanedValue, $indicator) !== false) {
                    $newFormatMatches++;
                    break;
                }
            }
        }

        // Check for OLD Format 1 (system headers: hh_no, last_name, etc.)
        $oldFormat1Indicators = [
            'hh_no', 'household_no',
            'last_name', 'lastname',
            'first_name', 'firstname',
            'middle_name', 'middlename',
            'sex', 'gender',
            'birth_date', 'bday', 'birthdate',
            'age',
            'birth_place', 'birthplace',
            'civil_status', 'civilstatus',
            'profession_private', 'profession_government',
        ];
        $oldFormat1Matches = 0;
        foreach ($oldFormat1Indicators as $indicator) {
            foreach ($firstRowValues as $value) {
                $cleanValue = strtolower(trim(strval($value)));
                if ($cleanValue === $indicator ||
                    $cleanValue === str_replace('_', '', $indicator) ||
                    stripos($cleanValue, $indicator) !== false ||
                    stripos($cleanValue, str_replace('_', '', $indicator)) !== false) {
                    $oldFormat1Matches++;
                    break;
                }
            }
        }

        // Check for OLD Format 2 (descriptive headers)
        $oldFormat2Indicators = [
            'hh no.', 'last name', 'first name', 'middle name', 's',
            'birth date', 'birth place', 'age', 'civil', 'relation to head',
            'educational attainment', 'grade/ course', 'school', 'profession/ occupation',
            'employment type', 'contact no.', 'fb email add', 'phic no.',
            'membership', 'family planning method used', 'sanitary toilet',
            'water supply', 'smoker', 'binge drinker', 'hpn', 'dm',
            "mother's maiden name", 'address', 'purok',
        ];
        $oldFormat2Matches = 0;
        foreach ($oldFormat2Indicators as $indicator) {
            foreach ($firstRowValues as $value) {
                $cleanedValue = strtolower(trim(strval($value)));
                if (stripos($cleanedValue, $indicator) !== false) {
                    $oldFormat2Matches++;
                    break;
                }
            }
        }

        Log::info('Format detection - New Format matches: '.$newFormatMatches.', Old Format1 matches: '.$oldFormat1Matches.', Old Format2 matches: '.$oldFormat2Matches);
        Log::info('First row values:', $firstRowValues);

        // Return the format with highest matches
        if ($newFormatMatches >= 5) {
            return 'new_format';
        }
        if ($oldFormat1Matches >= 4) {
            return 'format1';
        }
        if ($oldFormat2Matches >= 5) {
            return 'format2';
        }

        // If uncertain, check column count patterns
        $columnCount = count($firstRowValues);
        if ($columnCount >= 29) {
            // Updated format has 29 columns
            return 'new_format';
        } elseif ($columnCount >= 30) {
            // Old Format 2
            return 'format2';
        } elseif ($columnCount >= 27 && $columnCount <= 28) {
            // Old Format 1
            return 'format1';
        }

        // Default to new format if uncertain
        return 'new_format';
    }

    private function parseRowByFormat($row, $formatType, $rowIndex)
    {
        if ($formatType === 'new_format') {
            return $this->parseNewFormatRow($row, $rowIndex);
        } elseif ($formatType === 'format1') {
            return $this->parseFormat1Row($row, $rowIndex);
        } else {
            return $this->parseFormat2Row($row, $rowIndex);
        }
    }

    private function parseNewFormatRow($row, $rowIndex)
    {
        $rowData = $row->toArray();
        Log::info("NewFormat Row {$rowIndex} data:", $rowData);

        // Convert to numeric array if it's associative (happens with WithHeadingRow)
        $rowData = array_values($rowData);

        // New Format Structure (29 columns):
        // 0: hh_no
        // 1: last_name
        // 2: first_name
        // 3: middle_name
        // 4: sex
        // 5: birth_date
        // 6: age
        // 7: birth_place
        // 8: civil_status
        // 9: relation_to_head
        // 10: grade_course
        // 11: school
        // 12: profession_private (YES/NO)
        // 13: profession_government (YES/NO)
        // 14: contact_no
        // 15: fb_email
        // 16: phic_no
        // 17: membership
        // 18: family_planning_method
        // 19: sanitary_toilet (YES/NO)
        // 20: water_supply_level (YES/NO)
        // 21: smoker (YES/NO)
        // 22: binge_drinker (YES/NO)
        // 23: hpn (YES/NO)
        // 24: dm (YES/NO)
        // 25: mother_maiden_name
        // 26: address
        // 27: purok

        // Parse profession/employment type from YES/NO columns
        $professionPrivate = $this->cleanValue($rowData[12] ?? null);
        $professionGovernment = $this->cleanValue($rowData[13] ?? null);
        $employmentType = null;
        if (strtoupper($professionPrivate) === 'YES' && strtoupper($professionGovernment) === 'NO') {
            $employmentType = 'PRIVATE';
        } elseif (strtoupper($professionPrivate) === 'NO' && strtoupper($professionGovernment) === 'YES') {
            $employmentType = 'GOVERNMENT';
        } elseif (strtoupper($professionPrivate) === 'YES' && strtoupper($professionGovernment) === 'YES') {
            $employmentType = 'BOTH';
        }

        return [
            'household_number_raw' => $this->cleanValue($rowData[0] ?? null),
            'last_name_raw' => $this->cleanValue($rowData[1] ?? null),
            'first_name_raw' => $this->cleanValue($rowData[2] ?? null),
            'middle_name_raw' => $this->cleanValue($rowData[3] ?? null),
            'sex_raw' => $this->parseGenderValue($this->cleanValue($rowData[4] ?? null)),
            'birth_date_raw' => $this->parseDateValue($this->cleanValue($rowData[5] ?? null)),
            'age_raw' => $this->cleanValue($rowData[6] ?? null),
            'age' => $this->parseAgeValue($this->cleanValue($rowData[6] ?? null)),
            'birth_place_raw' => $this->cleanValue($rowData[7] ?? null),
            'civil_status_raw' => $this->parseCivilStatusValue($this->cleanValue($rowData[8] ?? null)),
            'relation_to_head_raw' => $this->cleanValue($rowData[9] ?? null),
            'grade_course_raw' => $this->cleanValue($rowData[10] ?? null),
            'school_raw' => $this->cleanValue($rowData[11] ?? null),
            'profession_occupation_raw' => null, // Not used in new format
            'employment_type_raw' => $employmentType,
            'contact_number_raw' => $this->cleanValue($rowData[14] ?? null),
            'fb_email_address_raw' => $this->cleanValue($rowData[15] ?? null),
            'phic_no_raw' => $this->cleanValue($rowData[16] ?? null),
            'membership_raw' => $this->cleanValue($rowData[17] ?? null),
            'family_planning_method_raw' => $this->cleanValue($rowData[18] ?? null),
            'sanitary_toilet_raw' => $this->parseBooleanValue($this->cleanValue($rowData[19] ?? null)),
            'water_supply_raw' => $this->parseWaterSupplyLevel($this->cleanValue($rowData[20] ?? null)),
            'smoker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[21] ?? null)),
            'binge_drinker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[22] ?? null)),
            'hpn_raw' => $this->parseBooleanValue($this->cleanValue($rowData[23] ?? null)),
            'dm_raw' => $this->parseBooleanValue($this->cleanValue($rowData[24] ?? null)),
            'pwd_raw' => null, // Placeholder for PWD
            'pwd_type_raw' => null, // Placeholder for PWD Type
            'mothers_maiden_name_raw' => $this->cleanValue($rowData[25] ?? null),
            'address_raw' => $this->cleanValue($rowData[26] ?? null),
            'purok_raw' => $this->cleanValue($rowData[27] ?? null),
            'date_registered' => now(),
        ];
    }

    private function parseFormat1Row($row, $rowIndex)
    {
        $rowData = $row->toArray();
        Log::info("Format1 Row {$rowIndex} data:", $rowData);

        // Convert to numeric array if it's associative (happens with WithHeadingRow)
        $rowData = array_values($rowData);

        // Format 1 (Old Structure): hh_no, last_name, first_name, middle_name, sex, birth_date, age, birth_place, civil_status, relation_to_head, grade_course, school, profession_private, profession_government, contact_no, fb_email, phic_no, membership, family_planning_method, sanitary_toilet, water_supply_level, smoker, binge_drinker, hpn, dm, mother_maiden_name, address, purok

        // Parse profession/employment type from YES/NO columns
        $professionPrivate = $this->cleanValue($rowData[12] ?? null);
        $professionGovernment = $this->cleanValue($rowData[13] ?? null);
        $employmentType = null;
        if (strtoupper($professionPrivate) === 'YES' && strtoupper($professionGovernment) === 'NO') {
            $employmentType = 'PRIVATE';
        } elseif (strtoupper($professionPrivate) === 'NO' && strtoupper($professionGovernment) === 'YES') {
            $employmentType = 'GOVERNMENT';
        } elseif (strtoupper($professionPrivate) === 'YES' && strtoupper($professionGovernment) === 'YES') {
            $employmentType = 'BOTH';
        }

        return [
            'household_number_raw' => $this->cleanValue($rowData[0] ?? null),
            'last_name_raw' => $this->cleanValue($rowData[1] ?? null),
            'first_name_raw' => $this->cleanValue($rowData[2] ?? null),
            'middle_name_raw' => $this->cleanValue($rowData[3] ?? null),
            'sex_raw' => $this->parseGenderValue($this->cleanValue($rowData[4] ?? null)),
            'birth_date_raw' => $this->parseDateValue($this->cleanValue($rowData[5] ?? null)),
            'age_raw' => $this->cleanValue($rowData[6] ?? null),
            'age' => $this->parseAgeValue($this->cleanValue($rowData[6] ?? null)),
            'birth_place_raw' => $this->cleanValue($rowData[7] ?? null),
            'civil_status_raw' => $this->parseCivilStatusValue($this->cleanValue($rowData[8] ?? null)),
            'relation_to_head_raw' => $this->cleanValue($rowData[9] ?? null),
            'grade_course_raw' => $this->cleanValue($rowData[10] ?? null),
            'school_raw' => $this->cleanValue($rowData[11] ?? null),
            'profession_occupation_raw' => null, // Not used in new format
            'employment_type_raw' => $employmentType,
            'contact_number_raw' => $this->cleanValue($rowData[14] ?? null),
            'fb_email_address_raw' => $this->cleanValue($rowData[15] ?? null),
            'phic_no_raw' => $this->cleanValue($rowData[16] ?? null),
            'membership_raw' => $this->cleanValue($rowData[17] ?? null),
            'family_planning_method_raw' => $this->cleanValue($rowData[18] ?? null),
            'sanitary_toilet_raw' => $this->parseBooleanValue($this->cleanValue($rowData[19] ?? null)),
            'water_supply_raw' => $this->parseWaterSupplyLevel($this->cleanValue($rowData[20] ?? null)),
            'smoker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[21] ?? null)),
            'binge_drinker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[22] ?? null)),
            'hpn_raw' => $this->parseBooleanValue($this->cleanValue($rowData[23] ?? null)),
            'dm_raw' => $this->parseBooleanValue($this->cleanValue($rowData[24] ?? null)),
            'pwd_raw' => null,
            'pwd_type_raw' => null,
            'mothers_maiden_name_raw' => $this->cleanValue($rowData[25] ?? null),
            'address_raw' => $this->cleanValue($rowData[26] ?? null),
            'purok_raw' => $this->cleanValue($rowData[27] ?? null),
            'date_registered' => now(),
        ];
    }

    private function parseFormat2Row($row, $rowIndex)
    {
        $rowData = $row->toArray();
        Log::info("Format2 Row {$rowIndex} data:", $rowData);

        // Convert to numeric array if it's associative (happens with WithHeadingRow)
        $rowData = array_values($rowData);

        // Actual Format 2 structure based on user's actual data:
        // 0: HH NO.
        // 1: LAST NAME
        // 2: FIRST NAME
        // 3: MIDDLE NAME
        // 4: S (sex)
        // 5: BIRTH DATE
        // 6: AGE
        // 7: BIRTH PLACE
        // 8: CIVIL STATUS
        // 9: RELATION TO HEAD
        // 10: GRADE/ COURSE
        // 11: SCHOOL
        // 12: (empty - separator)
        // 13: EMPLOYMENT TYPE (like "yes gov", "yes private", "no private", "no gov")
        // 14: CONTACT NO.
        // 15: FB EMAIL ADD
        // 16: PHIC NO.
        // 17: MEMBERSHIP
        // 18: FAMILY PLANNING METHOD USED
        // 19: SANITARY TOILET
        // 20: WATER SUPPLY
        // 21: SMOKER
        // 22: BINGE DRINKER
        // 23: HPN
        // 24: DM
        // 25: MOTHER'S MAIDEN NAME (LAST NAME)
        // 26: MOTHER'S MAIDEN NAME (FIRST NAME)
        // 27: MOTHER'S MAIDEN NAME (MIDDLE NAME)
        // 28: ADDRESS
        // 29: PUROK

        // Combine mother's maiden name parts
        $mothersLastName = $this->cleanValue($rowData[25] ?? null);
        $mothersFirstName = $this->cleanValue($rowData[26] ?? null);
        $mothersMiddleName = $this->cleanValue($rowData[27] ?? null);
        $mothersFullName = trim(implode(' ', array_filter([$mothersLastName, $mothersFirstName, $mothersMiddleName])));

        return [
            'household_number_raw' => $this->cleanValue($rowData[0] ?? null),
            'last_name_raw' => $this->cleanValue($rowData[1] ?? null),
            'first_name_raw' => $this->cleanValue($rowData[2] ?? null),
            'middle_name_raw' => $this->cleanValue($rowData[3] ?? null),
            'sex_raw' => $this->parseGenderValue($this->cleanValue($rowData[4] ?? null)),
            'birth_date_raw' => $this->parseDateValue($this->cleanValue($rowData[5] ?? null)),
            'age_raw' => $this->cleanValue($rowData[6] ?? null),
            'age' => $this->parseAgeValue($this->cleanValue($rowData[6] ?? null)),
            'birth_place_raw' => $this->cleanValue($rowData[7] ?? null),
            'civil_status_raw' => $this->parseCivilStatusValue($this->cleanValue($rowData[8] ?? null)),
            'relation_to_head_raw' => $this->cleanValue($rowData[9] ?? null),
            'educational_attainment_raw' => null, // Not in this format
            'grade_course_raw' => $this->cleanValue($rowData[10] ?? null),
            'school_raw' => $this->cleanValue($rowData[11] ?? null),
            'profession_occupation_raw' => null, // Not separate in this format
            'employment_type_raw' => $this->parseEmploymentTypeValue($this->cleanValue($rowData[13] ?? null)),
            'contact_number_raw' => $this->cleanValue($rowData[14] ?? null),
            'fb_email_address_raw' => $this->cleanValue($rowData[15] ?? null),
            'phic_no_raw' => $this->cleanValue($rowData[16] ?? null),
            'membership_raw' => $this->cleanValue($rowData[17] ?? null),
            'family_planning_method_raw' => $this->cleanValue($rowData[18] ?? null),
            'sanitary_toilet_raw' => $this->parseBooleanValue($this->cleanValue($rowData[19] ?? null)),
            'water_supply_raw' => $this->parseWaterSupplyLevel($this->cleanValue($rowData[20] ?? null)),
            'smoker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[21] ?? null)),
            'binge_drinker_raw' => $this->parseBooleanValue($this->cleanValue($rowData[22] ?? null)),
            'hpn_raw' => $this->parseBooleanValue($this->cleanValue($rowData[23] ?? null)),
            'dm_raw' => $this->parseBooleanValue($this->cleanValue($rowData[24] ?? null)),
            'pwd_raw' => null,
            'pwd_type_raw' => null,
            'mothers_maiden_name_raw' => $mothersFullName ?: null,
            'address_raw' => $this->cleanValue($rowData[28] ?? null),
            'purok_raw' => $this->cleanValue($rowData[29] ?? null),
            'date_registered' => now(),
        ];
    }

    private function cleanValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleaned = trim(strval($value));

        // Handle Excel errors
        if (stripos($cleaned, '#REF!') !== false ||
            stripos($cleaned, '#N/A') !== false ||
            stripos($cleaned, '#VALUE!') !== false) {
            return null;
        }

        // Handle empty strings
        if ($cleaned === '') {
            return null;
        }

        return $cleaned;
    }

    private function isRowEmpty($row)
    {
        foreach ($row as $value) {
            if (! empty(trim(strval($value))) &&
                stripos(strval($value), '#REF!') === false &&
                stripos(strval($value), '#N/A') === false) {
                return false;
            }
        }

        return true;
    }

    // ... rest of the parsing methods remain the same ...

    private function parseGenderValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleanValue = strtolower(trim($value));

        if (in_array($cleanValue, ['m', 'male', 'lalaki', 'man'])) {
            return 'Male';
        } elseif (in_array($cleanValue, ['f', 'female', 'babae', 'woman'])) {
            return 'Female';
        }

        return ucfirst($cleanValue);
    }

    private function parseDateValue($value)
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $cleanValue = trim($value);

        // Skip obvious non-dates
        if (in_array(strtolower($cleanValue), ['bday', 'birth', 'date', '#ref!', 'birth date'])) {
            return null;
        }

        // Handle Excel serial date numbers (like 36787, 40170 seen in logs)
        if (is_numeric($cleanValue) && $cleanValue > 1000 && $cleanValue < 100000) {
            // Excel serial date: days since 1900-01-01
            // But Excel has a bug where it treats 1900 as leap year
            // So we subtract 2 days to compensate
            $excelBaseDate = \Carbon\Carbon::create(1900, 1, 1);
            $date = $excelBaseDate->addDays((int) $cleanValue - 2);

            return $date;
        }

        // Handle MM/DD/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $cleanValue, $matches)) {
            try {
                $date = \Carbon\Carbon::createFromFormat('m/d/Y', $cleanValue);

                return $date;
            } catch (\Exception $e) {
                // Try other formats
            }
        }

        // Try standard date parsing
        try {
            $date = \Carbon\Carbon::parse($cleanValue);

            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAgeValue($value)
    {
        if (is_null($value) || trim($value) === '' || stripos($value, '#REF!') !== false) {
            return null;
        }

        // Handle Excel formulas like =DATEDIF(F21,#REF!,"Y")&"yrs."
        if (strpos($value, '=DATEDIF') !== false) {
            // Try to extract numeric age from formula
            // Look for patterns like "23" or "23yrs"
            if (preg_match('/\d+/', $value, $matches)) {
                $age = (int) $matches[0];

                return ($age >= 0 && $age <= 120) ? $age : null;
            }

            return null;
        }

        // Handle text with "yrs" like "23yrs"
        if (preg_match('/(\d+)\s*yrs?\.?/i', $value, $matches)) {
            $age = (int) $matches[1];

            return ($age >= 0 && $age <= 120) ? $age : null;
        }

        // Handle pure numeric values
        if (is_numeric($value)) {
            $age = (int) $value;

            return ($age >= 0 && $age <= 120) ? $age : null;
        }

        return null;
    }

    private function parseCivilStatusValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleanValue = strtolower(trim($value));

        $mapping = [
            'single' => 'Single',
            'married' => 'Married',
            'widow' => 'Widowed',
            'widow/er' => 'Widowed',
            'widower' => 'Widowed',
            'divorced' => 'Divorced',
            'separated' => 'Separated',
        ];

        foreach ($mapping as $key => $mapped) {
            if (strpos($cleanValue, $key) !== false) {
                return $mapped;
            }
        }

        return ucfirst($cleanValue);
    }

    private function parseBooleanValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleanValue = strtolower(trim($value));

        if (in_array($cleanValue, ['y', 'yes', '1', 'true', 'si', 'oo'])) {
            return 1;
        } elseif (in_array($cleanValue, ['n', 'no', '0', 'false', 'hindi'])) {
            return 0;
        }

        return null;
    }

    private function parseWaterSupplyLevel($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleanValue = strtolower(trim($value));

        // Handle L-1, L-2, L-3 format
        if (preg_match('/^l[-_\s]?(\d+)$/i', $cleanValue, $matches)) {
            $number = intval($matches[1]);
            switch ($number) {
                case 1: return 'I';
                case 2: return 'II';
                case 3: return 'III';
                default: return null;
            }
        }

        // Handle direct roman numerals
        if (in_array(strtoupper($cleanValue), ['I', 'II', 'III'])) {
            return strtoupper($cleanValue);
        }

        // Handle numeric values
        if (is_numeric($cleanValue)) {
            $number = intval($cleanValue);
            switch ($number) {
                case 1: return 'I';
                case 2: return 'II';
                case 3: return 'III';
                default: return null;
            }
        }

        return null;
    }

    private function parseEmploymentTypeValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $cleanValue = strtoupper(trim($value));

        if (strpos($cleanValue, 'GOV') !== false || strpos($cleanValue, 'GOVERN') !== false) {
            return 'GOVERNMENT';
        } elseif (strpos($cleanValue, 'PRIV') !== false || strpos($cleanValue, 'PRIVATE') !== false) {
            return 'PRIVATE';
        }

        return $cleanValue;
    }

    private function validateParsedData($parsedData, $formatType)
    {
        $rules = [
            'first_name_raw' => 'required|string|max:255',
            'last_name_raw' => 'required|string|max:255',
            'purok_raw' => 'required|string|max:255',
            'sex_raw' => 'required|in:Male,Female',
            'birth_date_raw' => 'required',
            'civil_status_raw' => 'nullable|string|max:255',
            'address_raw' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($parsedData, $rules);

        Log::debug('Validation result', [
            'fails' => $validator->fails(),
            'errors' => $validator->errors()->all(),
            'data_keys' => array_keys($parsedData),
            'first_name' => $parsedData['first_name_raw'] ?? 'NOT SET',
            'last_name' => $parsedData['last_name_raw'] ?? 'NOT SET',
        ]);

        return $validator;
    }
}
