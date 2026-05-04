<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Resident;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurokController extends Controller
{
    public function destroy(Purok $purok)
    {
        $hasResidents = Resident::where('purok_id', $purok->purok_id)->exists();
        if ($hasResidents) {
            return redirect()
                ->route('puroks.show', $purok)
                ->with('error', 'Cannot delete this Purok because it has residents.');
        }

        $purok->delete();

        return redirect()
            ->route('dashboard')
            ->with('message', 'Purok deleted successfully.');
    }


    public function create()
    {
        return view('puroks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purok_name' => ['required', 'string', 'max:255'],
        ]);

        Purok::create($validated);

        return redirect()->route('dashboard');
    }

    public function show(Request $request, Purok $purok)
{
    $residentIds = Resident::where('purok_id', $purok->purok_id)->pluck('resident_id');

    $totalResidents = $residentIds->count();
    $totalSeniors = Resident::whereIn('resident_id', $residentIds)
        ->whereNotNull('birth_date')
        ->get()
        ->filter(function ($resident) {
            return $resident->age >= 60;
        })->count();

    $totalPwd = Resident::whereIn('resident_id', $residentIds)
        ->where('pwd', 1)
        ->count();

    $filter = $request->query('filter', 'all');
    $sort = $request->query('sort', 'asc');
    $search = trim($request->query('search', ''));

    $residentsQuery = Resident::whereIn('resident_id', $residentIds);

    if ($filter === 'male') {
        $residentsQuery->where('sex', 'Male');
    } elseif ($filter === 'female') {
        $residentsQuery->where('sex', 'Female');
    } elseif ($filter === 'pwd') {
        $residentsQuery->where('pwd', 1);
    }

    if ($search !== '') {
        $residentsQuery->where(function ($q) use ($search) {
            $q->where('first_name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('middle_name', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhere('household_number', 'like', '%'.$search.'%');
        });
    }

    $sortDirection = $sort === 'desc' ? 'desc' : 'asc';
    $residents = $residentsQuery
        ->orderBy('last_name', $sortDirection)
        ->orderBy('first_name', $sortDirection)
        ->paginate(20)
        ->withQueryString();

    return view('puroks.show', compact('purok', 'residents', 'totalResidents', 'totalSeniors', 'totalPwd'));
}

    public function exportConsolidation(Request $request, Purok $purok)
    {
        $year = (int)($request->query('year', now()->year));

        $purokId = $purok->purok_id;

        $population = Resident::where('purok_id', $purok->purok_id)->count();
        $households = Resident::where('purok_id', $purok->purok_id)
            ->whereNotNull('household_number')
            ->pluck('household_number')
            ->unique()
            ->count();

        $rows = [];

        $rows[] = ['REPUBLIC OF THE PHILIPPINES', '', '', '', ''];
        $rows[] = ['PROVINCE OF AGUSAN DEL NORTE', '', '', '', ''];
        $rows[] = ['MUNICIPALITY OF MAGALLANES', '', '', '', ''];
        $rows[] = ['BARANGAY STO ROSARIO', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['ANNUAL CONSOLIDATION OF AGE GROUPING REPORT', '', '', '', ''];
        $rows[] = ['Calendar Year '.$year, '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['PUROK '.$purok->purok_name, '', 'POPULATION: '.$population, '', ''];
        $rows[] = ['', '', 'NO. OF HH: '.$households, '', ''];
        $rows[] = ['AGE', 'MALE', 'FEMALE', 'TOTAL', 'REMARKS'];

        $countBand = function (string $condition) use ($purokId) {
            $male = Resident::where('purok_id', $purokId)
                ->whereNotNull('birth_date')
                ->where('sex', 'Male')
                ->whereRaw($condition)
                ->count();
            $female = Resident::where('purok_id', $purokId)
                ->whereNotNull('birth_date')
                ->where('sex', 'Female')
                ->whereRaw($condition)
                ->count();
            return [$male, $female];
        };

        [$m1, $f1] = $countBand('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) = 0 AND TIMESTAMPDIFF(MONTH, birth_date, CURDATE()) BETWEEN 0 AND 6');
        $rows[] = ['0-6 months', $m1, $f1, $m1 + $f1, ''];
        [$m2, $f2] = $countBand('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) = 0 AND TIMESTAMPDIFF(MONTH, birth_date, CURDATE()) BETWEEN 7 AND 11');
        $rows[] = ['7-11 months', $m2, $f2, $m2 + $f2, ''];

        for ($age = 1; $age <= 59; $age++) {
            [$ma, $fa] = $countBand('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) = '.$age);
            $rows[] = [$age.' year'.($age > 1 ? 's' : '').' old', $ma, $fa, $ma + $fa, ''];
        }

        foreach ([
            ['60-64 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 60 AND 64'],
            ['65-69 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 65 AND 69'],
            ['70-74 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 70 AND 74'],
            ['75-79 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 75 AND 79'],
            ['80-84 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 80 AND 84'],
            ['85-90 years old', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 85 AND 90'],
            ['91 years old above', 'TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 91'],
        ] as [$label, $cond]) {
            [$mb, $fb] = $countBand($cond);
            $rows[] = [$label, $mb, $fb, $mb + $fb, ''];
        }

        $maleTotal = Resident::where('purok_id', $purok->purok_id)
            ->whereNotNull('birth_date')
            ->where('sex', 'Male')
            ->count();
        $femaleTotal = Resident::where('purok_id', $purok->purok_id)
            ->whereNotNull('birth_date')
            ->where('sex', 'Female')
            ->count();
        $rows[] = ['TOTAL', (int)$maleTotal, (int)$femaleTotal, (int)($maleTotal + $femaleTotal), ''];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $r = 1;
        foreach ($rows as $row) {
            $sheet->setCellValueByColumnAndRow(1, $r, $row[0]);
            $sheet->setCellValueByColumnAndRow(2, $r, $row[1]);
            $sheet->setCellValueByColumnAndRow(3, $r, $row[2]);
            $sheet->setCellValueByColumnAndRow(4, $r, $row[3]);
            $sheet->setCellValueByColumnAndRow(5, $r, $row[4]);
            $r++;
        }

        $fileName = 'Consolidation_Purok_'.$purok->purok_name.'_'.$year.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tmpFile);

        return response()->download($tmpFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportResidents(Purok $purok)
    {
        $residents = Resident::where('purok_id', $purok->purok_id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Resident ID',
            'Last Name',
            'First Name',
            'Middle Name',
            'Suffix',
            'Sex',
            'Birth Date',
            'Age',
            'Civil Status',
            'Relation to Head',
            'Address',
            'Household Number',
            'Purok',
            'Contact Number',
            'FB/Email',
            'PHIC No',
            'Membership',
            'Family Planning Method',
            'Sanitary Toilet',
            'Water Supply',
            'Smoker',
            'Binge Drinker',
            'HPN',
            'DM',
            'PWD',
            'PWD Type',
            'Date Registered',
            'Residency Status',
            'Educational Attainment',
            'Grade/Course',
            'School',
            'Profession/Occupation',
            'Employment Type',
            'Mother\'s Maiden Name',
        ];

        $c = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($c, 1, $h);
            $c++;
        }

        $r = 2;
        foreach ($residents as $resident) {
            $row = [
                $resident->resident_id,
                $resident->last_name,
                $resident->first_name,
                $resident->middle_name,
                $resident->suffix,
                $resident->sex,
                $resident->birth_date ? $resident->birth_date->format('Y-m-d') : null,
                $resident->age,
                $resident->civil_status,
                $resident->relation_to_head,
                $resident->address,
                $resident->household_number,
                $resident->purok ? $resident->purok->purok_name : null,
                $resident->contact_number,
                $resident->fb_email_address,
                $resident->phic_no,
                $resident->membership,
                $resident->family_planning_method,
                $resident->sanitary_toilet ? 'Y' : ($resident->getRawOriginal('sanitary_toilet') === null ? '-' : 'N'),
                $resident->water_supply,
                $resident->smoker ? 'Y' : ($resident->getRawOriginal('smoker') === null ? '-' : 'N'),
                $resident->binge_drinker ? 'Y' : ($resident->getRawOriginal('binge_drinker') === null ? '-' : 'N'),
                $resident->hpn ? 'Y' : ($resident->getRawOriginal('hpn') === null ? '-' : 'N'),
                $resident->dm ? 'Y' : ($resident->getRawOriginal('dm') === null ? '-' : 'N'),
                $resident->pwd ? 'Y' : ($resident->getRawOriginal('pwd') === null ? '-' : 'N'),
                $resident->pwd_type,
                $resident->date_registered ? $resident->date_registered->format('Y-m-d') : null,
                $resident->residency_status,
                $resident->educational_attainment,
                $resident->grade_course,
                $resident->school,
                $resident->profession_occupation,
                $resident->employment_type,
                $resident->mothers_maiden_name,
            ];

            $col = 1;
            foreach ($row as $val) {
                $sheet->setCellValueByColumnAndRow($col, $r, $val);
                $col++;
            }
            $r++;
        }

        $fileName = 'Residents_Purok_'.$purok->purok_name.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tmpFile);

        return response()->download($tmpFile, $fileName)->deleteFileAfterSend(true);
    }
}
