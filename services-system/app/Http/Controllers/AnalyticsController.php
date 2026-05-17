<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\CertificateRequest;
use App\Models\IncidentType;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class AnalyticsController extends Controller
{
//    public function index(Request $request)
// {
//     $filterType = $request->input('filter_type', 'month');
//     $date  = $request->input('date',  now()->format('Y-m-d'));
//     $month = $request->input('month', now()->format('Y-m'));
//     $year  = $request->input('year',  now()->format('Y'));

//     if (blank($date))  $date  = now()->format('Y-m-d');
//     if (blank($month)) $month = now()->format('Y-m');
//     if (blank($year))  $year  = now()->format('Y');

//     $certQuery = CertificateRequest::with('certificateType');
//     $certQuery = $this->applyDateFilter($certQuery, $filterType, $date, $month, $year, 'date_requested');

//     $certificateStats = $certQuery->get()
//         ->groupBy(fn($r) => $r->certificateType?->certificate_name ?? 'Unknown')
//         ->map(fn($group) => $group->count())
//         ->sortDesc();

//     $blotterQuery = BlotterRecord::query();
//     $blotterQuery = $this->applyDateFilter($blotterQuery, $filterType, $date, $month, $year, 'created_at');

//     $blotterStats = $blotterQuery
//         ->selectRaw('form_id, COUNT(*) as total')
//         ->groupBy('form_id')
//         ->get()
//         ->mapWithKeys(fn($row) => [
//             ($row->form_id ? 'KP Form ' . $row->form_id : 'Unspecified') => $row->total
//         ])
//         ->sortDesc();

        

//     // ── Case subject stats from form_data JSON ──
//    // ── Case subject stats from form_data JSON ──
// $blotterQuery2 = BlotterRecord::query();
// $blotterQuery2 = $this->applyDateFilter($blotterQuery2, $filterType, $date, $month, $year, 'created_at');

// // Get the filtered blotter IDs first
// $filteredBlotterIds = $this->applyDateFilter(
//     BlotterRecord::query(), $filterType, $date, $month, $year, 'created_at'
// )->pluck('blotter_id');

// $incidentTypeStats = \App\Models\IncidentType::query()
//     ->withCount(['blotterRecords' => function ($q) use ($filteredBlotterIds) {
//         $q->whereIn('blotter_records.blotter_id', $filteredBlotterIds);
//     }])
//     ->get()
//     ->filter(fn($t) => $t->blotter_records_count > 0)
//     ->sortByDesc('blotter_records_count')
//     ->mapWithKeys(fn($t) => [$t->name => $t->blotter_records_count]);

    
//     $totalCerts    = $certificateStats->sum();
//     $totalBlotters = $blotterStats->sum();
//     $filters       = compact('filterType', 'date', 'month', 'year');
// // Temporarily add this in your controller to debug
// // dd($incidentTypeStats); // remove after confirming
// return view('Dashboard', compact(
//     'certificateStats', 'blotterStats',
//     'totalCerts', 'totalBlotters',
//     'incidentTypeStats',    // ← replace caseSubjectStats
//     'filters'
// ));
// }


public function index(Request $request)
{
    $filterType = $request->input('filter_type', 'month');
    $date  = $request->input('date',  now()->format('Y-m-d'));
    $month = $request->input('month', now()->format('Y-m'));
    $year  = $request->input('year',  now()->format('Y'));

    if (blank($date))  $date  = now()->format('Y-m-d');
    if (blank($month)) $month = now()->format('Y-m');
    if (blank($year))  $year  = now()->format('Y');

    $certQuery = CertificateRequest::with('certificateType');
    $certQuery = $this->applyDateFilter($certQuery, $filterType, $date, $month, $year, 'date_requested');

    $certificateStats = $certQuery->get()
        ->groupBy(fn($r) => $r->certificateType?->certificate_name ?? 'Unknown')
        ->map(fn($group) => $group->count())
        ->sortDesc();

    $blotterQuery = BlotterRecord::query();
    $blotterQuery = $this->applyDateFilter($blotterQuery, $filterType, $date, $month, $year, 'created_at');

    $blotterStats = $blotterQuery
        ->selectRaw('form_id, COUNT(*) as total')
        ->groupBy('form_id')
        ->get()
        ->mapWithKeys(fn($row) => [
            ($row->form_id ? 'KP Form ' . $row->form_id : 'Unspecified') => $row->total
        ])
        ->sortDesc();

    // ── Case subject stats from form_data JSON ──
    $blotterQuery2 = BlotterRecord::query();
    $blotterQuery2 = $this->applyDateFilter($blotterQuery2, $filterType, $date, $month, $year, 'created_at');

    $caseSubjectStats = $blotterQuery2
        ->whereNotNull('form_data')
        ->get()
        ->map(fn($r) => $r->form_data['case_subject'] ?? null)
        ->filter()
        ->map(fn($s) => trim($s))
        ->filter(fn($s) => $s !== '')
        ->countBy()
        ->sortDesc();

   $purokStats = \DB::table('blotter_purok')
    ->join('puroks', 'puroks.purok_id', '=', 'blotter_purok.purok_id')
    ->join('blotter_records', 'blotter_records.blotter_id', '=', 'blotter_purok.blotter_id')
    ->leftJoin('incident_areas', 'incident_areas.id', '=', 'blotter_purok.area_id')  // ← new join
    ->when($filterType === 'date',  fn($q) => $q->whereDate('blotter_records.created_at', $date))
    ->when($filterType === 'month', fn($q) => $q->whereYear('blotter_records.created_at', substr($month, 0, 4))
                                                  ->whereMonth('blotter_records.created_at', substr($month, 5, 2)))
    ->when($filterType === 'year',  fn($q) => $q->whereYear('blotter_records.created_at', $year))
    ->whereNotNull('blotter_records.form_data')
    ->select(
        'puroks.purok_name',
        'incident_areas.name as incident_area',   // ← resolved name instead of raw string
        'blotter_records.form_data',
        'blotter_records.blotter_id'
    )
    ->get()
    ->groupBy('purok_name')
    ->map(function ($records) {
        $subjects = $records
            ->map(function ($r) {
                $data = is_array($r->form_data)
                    ? $r->form_data
                    : json_decode($r->form_data, true) ?? [];
                return $data['case_subject'] ?? null;
            })
            ->filter()
            ->map(fn($s) => trim($s))
            ->filter(fn($s) => $s !== '')
            ->countBy()
            ->sortDesc();
 
        $areas = $records
            ->pluck('incident_area')        // now comes from incident_areas.name via the LEFT JOIN
            ->filter()
            ->map(fn($a) => trim($a))
            ->filter(fn($a) => $a !== '')
            ->countBy()
            ->sortDesc();
 
        return [
            'subjects' => $subjects,
            'areas'    => $areas,
            'total'    => $records->count(),
        ];
    })
    ->sortKeys();
 
// All unique case subjects across all puroks
$allCaseSubjects = collect($purokStats->pluck('subjects'))
    ->flatMap(fn($s) => $s->keys())
    ->unique()
    ->sort()
    ->values();

        $totalCerts    = $certificateStats->sum();
    $totalBlotters = $blotterStats->sum();
    $filters       = compact('filterType', 'date', 'month', 'year');

   return view('Dashboard', compact(
    'certificateStats', 'blotterStats',
    'totalCerts', 'totalBlotters',
    'caseSubjectStats',
    'purokStats',
    'allCaseSubjects',
    'filters'
));
}


public function blotterTest(Request $request)
{
    $filterType = 'all';
    $date  = now()->format('Y-m-d');
    $month = now()->format('Y-m');
    $year  = now()->format('Y');

    $blotterQuery = BlotterRecord::query();
    $blotterStats = $blotterQuery
        ->selectRaw('form_id, COUNT(*) as total')
        ->groupBy('form_id')
        ->get()
        ->mapWithKeys(fn($row) => [
            ($row->form_id ? 'KP Form ' . $row->form_id : 'Unspecified') => $row->total
        ])
        ->sortDesc();

    $filteredBlotterIds = BlotterRecord::pluck('blotter_id');

    $incidentTypeStats = \App\Models\IncidentType::query()
        ->withCount(['blotterRecords' => function ($q) use ($filteredBlotterIds) {
            $q->whereIn('blotter_records.blotter_id', $filteredBlotterIds);
        }])
        ->get()
        ->filter(fn($t) => $t->blotter_records_count > 0)
        ->sortByDesc('blotter_records_count')
        ->mapWithKeys(fn($t) => [$t->name => $t->blotter_records_count]);

    $totalBlotters = $blotterStats->sum();

    return view('blotter-test', compact('blotterStats', 'incidentTypeStats', 'totalBlotters'));
}

    public function exportReport(Request $request)
{
    $filterType = $request->input('filter_type', 'month');
    $date       = $request->input('date',   now()->format('Y-m-d'));
    $month      = $request->input('month',  now()->format('Y-m'));
    $year       = $request->input('year',   now()->format('Y'));
    $panel      = $request->input('panel',  'cert');

    if (blank($date))  $date  = now()->format('Y-m-d');
    if (blank($month)) $month = now()->format('Y-m');
    if (blank($year))  $year  = now()->format('Y');

    $periodLabel = match ($filterType) {
        'date'  => 'Date: ' . \Carbon\Carbon::parse($date)->format('F j, Y'),
        'month' => 'Month: ' . \Carbon\Carbon::parse($month . '-01')->format('F Y'),
        'year'  => 'Year: ' . $year,
        default => 'All Time',
    };

    $panelName   = $panel === 'blotter' ? 'Blotter Records' : 'Certificate Requests';
    $reportTitle = match ($filterType) {
        'date'  => "{$panelName} Report for " . \Carbon\Carbon::parse($date)->format('F j, Y'),
        'month' => "{$panelName} Report for " . \Carbon\Carbon::parse($month . '-01')->format('F Y'),
        'year'  => "{$panelName} Report for Year {$year}",
        default => "{$panelName} Report (All Time)",
    };

    // ── Main stats ────────────────────────────────────────────────
    if ($panel === 'blotter') {
        $query = $this->applyDateFilter(BlotterRecord::query(), $filterType, $date, $month, $year, 'created_at');
        $stats = $query->selectRaw('form_id, COUNT(*) as total')
            ->groupBy('form_id')->get()
            ->mapWithKeys(fn($row) => [($row->form_id ? 'KP Form ' . $row->form_id : 'Unspecified') => $row->total])
            ->sortDesc();
        $total     = $stats->sum();
        $colHeader = 'KP Form Type';

        // ── Purok heatmap data ────────────────────────────────────
        $purokStats = \DB::table('blotter_purok')
    ->join('puroks', 'puroks.purok_id', '=', 'blotter_purok.purok_id')
    ->join('blotter_records', 'blotter_records.blotter_id', '=', 'blotter_purok.blotter_id')
    ->leftJoin('incident_areas', 'incident_areas.id', '=', 'blotter_purok.area_id')

    ->when($filterType === 'date', fn($q) =>
        $q->whereDate('blotter_records.created_at', $date)
    )

    ->when($filterType === 'month', fn($q) =>
        $q->whereYear('blotter_records.created_at', substr($month, 0, 4))
          ->whereMonth('blotter_records.created_at', substr($month, 5, 2))
    )

    ->when($filterType === 'year', fn($q) =>
        $q->whereYear('blotter_records.created_at', $year)
    )

    ->whereNotNull('blotter_records.form_data')

    ->select(
        'puroks.purok_name',
        'incident_areas.name as incident_area',
        'blotter_records.form_data'
    )

    ->get()

    ->groupBy('purok_name')

    ->map(function ($records) {

        $subjects = $records->map(function ($r) {
            $data = is_array($r->form_data)
                ? $r->form_data
                : json_decode($r->form_data, true) ?? [];

            return $data['case_subject'] ?? null;
        })
        ->filter()
        ->map(fn($s) => trim($s))
        ->filter(fn($s) => $s !== '')
        ->countBy()
        ->sortDesc();

        $areas = $records->pluck('incident_area')
            ->filter()
            ->map(fn($a) => trim($a))
            ->filter(fn($a) => $a !== '')
            ->countBy()
            ->sortDesc();

        return [
            'subjects' => $subjects,
            'areas'    => $areas,
            'total'    => $records->count(),
        ];
    })

    ->sortKeys();

        $allCaseSubjects = collect($purokStats->pluck('subjects'))
            ->flatMap(fn($s) => $s->keys())->unique()->sort()->values();

    } else {
        $query = $this->applyDateFilter(CertificateRequest::with('certificateType'), $filterType, $date, $month, $year, 'date_requested');
        $stats = $query->get()
            ->groupBy(fn($r) => $r->certificateType?->certificate_name ?? 'Unknown')
            ->map(fn($group) => $group->count())->sortDesc();
        $total          = $stats->sum();
        $colHeader      = 'Certificate Type';
        $purokStats     = collect();
        $allCaseSubjects = collect();
    }

    // ── Build DOCX ────────────────────────────────────────────────
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Calibri');
    $phpWord->setDefaultFontSize(11);

    $section = $phpWord->addSection([
        'marginTop'    => Converter::cmToTwip(2),
        'marginBottom' => Converter::cmToTwip(2),
        'marginLeft'   => Converter::cmToTwip(2.5),
        'marginRight'  => Converter::cmToTwip(2.5),
    ]);

    // Title
    $section->addText($reportTitle, ['bold' => true, 'size' => 14], ['alignment' => 'center', 'spaceAfter' => 80]);
    $section->addText(
        $periodLabel . '   •   Generated: ' . now()->format('F j, Y g:i A'),
        ['size' => 9, 'color' => '888888', 'italic' => true],
        ['alignment' => 'center', 'spaceAfter' => 300]
    );
    $section->addText('Total Records: ' . $total, ['bold' => true, 'size' => 11], ['spaceAfter' => 200]);

    // ── Main stats table ──────────────────────────────────────────
    $table = $section->addTable([
        'borderSize' => 6, 'borderColor' => 'e4e4e7',
        'cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct',
    ]);

    $table->addRow(500);
    foreach (['#', $colHeader, 'Count', 'Share %'] as $heading) {
        $table->addCell(null, ['bgColor' => 'f4f4f5'])
              ->addText($heading, ['bold' => true, 'size' => 10, 'color' => '52525b']);
    }

    $i = 1;
    foreach ($stats as $label => $count) {
        $pct   = $total > 0 ? round($count / $total * 100, 1) : 0;
        $rowBg = $i % 2 === 0 ? 'fafafa' : 'ffffff';
        $table->addRow(400);
        $table->addCell(null, ['bgColor' => $rowBg])->addText((string) $i,     ['size' => 10, 'color' => '71717a']);
        $table->addCell(null, ['bgColor' => $rowBg])->addText($label,          ['size' => 10]);
        $table->addCell(null, ['bgColor' => $rowBg])->addText((string) $count, ['bold' => true, 'size' => 10]);
        $table->addCell(null, ['bgColor' => $rowBg])->addText($pct . '%',      ['size' => 10, 'color' => '71717a']);
        $i++;
    }

    $table->addRow(450);
    $table->addCell(null, ['bgColor' => 'f4f4f5'])->addText('',        []);
    $table->addCell(null, ['bgColor' => 'f4f4f5'])->addText('Total',   ['bold' => true, 'size' => 10]);
    $table->addCell(null, ['bgColor' => 'f4f4f5'])->addText((string) $total, ['bold' => true, 'size' => 10]);
    $table->addCell(null, ['bgColor' => 'f4f4f5'])->addText('100%',    ['bold' => true, 'size' => 10]);

    // ── Purok heatmap section (blotter only) ──────────────────────
    if ($panel === 'blotter' && $purokStats->isNotEmpty() && $allCaseSubjects->isNotEmpty()) {
        $section->addTextBreak(2);
        $section->addText('Incident Hotspot Map — Case Subjects by Purok',
            ['bold' => true, 'size' => 12], ['spaceAfter' => 100]);
        $section->addText('Darker cells indicate higher frequency.',
            ['size' => 9, 'color' => '888888', 'italic' => true], ['spaceAfter' => 200]);

        // Heatmap table — columns: Purok | [subject cols] | Total
        $colCount = $allCaseSubjects->count() + 2; // purok + subjects + total
        $heatTable = $section->addTable([
            'borderSize' => 4, 'borderColor' => 'e4e4e7',
            'cellMargin' => 60, 'width' => 100 * 50, 'unit' => 'pct',
        ]);

        // Header row
        $heatTable->addRow(400);
        $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                  ->addText('Purok', ['bold' => true, 'size' => 9, 'color' => '52525b']);
        foreach ($allCaseSubjects as $subject) {
            $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                      ->addText($subject, ['bold' => true, 'size' => 8, 'color' => '52525b']);
        }
        $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                  ->addText('Total', ['bold' => true, 'size' => 9, 'color' => '52525b']);

        $heatMax = collect($purokStats)->map(fn($d) => $d['subjects']->values()->max() ?? 0)->max();

        // Data rows
        foreach ($purokStats as $purokName => $data) {
            $purokTotal = $data['subjects']->sum();
            $heatTable->addRow(380);
            $heatTable->addCell(null, ['bgColor' => 'ffffff'])
                      ->addText($purokName, ['size' => 9, 'bold' => true]);

            foreach ($allCaseSubjects as $subject) {
                $val       = $data['subjects']->get($subject, 0);
                $intensity = $heatMax > 0 ? $val / $heatMax : 0;
                $alpha     = $val > 0 ? $intensity * 0.87 + 0.08 : 0;

                // Convert alpha to hex-blended indigo (#6366f1) on white
                $r = (int) round(255 - (255 - 99)  * $alpha);
                $g = (int) round(255 - (255 - 102) * $alpha);
                $b = (int) round(255 - (255 - 241) * $alpha);
                $cellBg = sprintf('%02x%02x%02x', $r, $g, $b);
                $textColor = $alpha > 0.5 ? 'ffffff' : ($val > 0 ? '3730a3' : '71717a');

                $heatTable->addCell(null, ['bgColor' => $cellBg])
                          ->addText($val > 0 ? (string) $val : '—',
                              ['size' => 9, 'bold' => $val > 0, 'color' => $textColor],
                              ['alignment' => 'center']);
            }

            $heatTable->addCell(null, ['bgColor' => 'f9fafb'])
                      ->addText((string) $purokTotal, ['bold' => true, 'size' => 9],
                          ['alignment' => 'right']);
        }

        // Column totals row
        $heatTable->addRow(380);
        $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                  ->addText('Total', ['bold' => true, 'size' => 9, 'color' => '52525b']);
        foreach ($allCaseSubjects as $subject) {
            $colTotal = collect($purokStats)->sum(fn($d) => $d['subjects']->get($subject, 0));
            $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                      ->addText((string) $colTotal, ['bold' => true, 'size' => 9],
                          ['alignment' => 'center']);
        }
        $heatTable->addCell(null, ['bgColor' => 'f4f4f5'])
                  ->addText((string) collect($purokStats)->sum(fn($d) => $d['subjects']->sum()),
                      ['bold' => true, 'size' => 9], ['alignment' => 'right']);

        // ── Incident Areas section ────────────────────────────────
        $hasAreas = collect($purokStats)->contains(fn($d) => $d['areas']->isNotEmpty());
        if ($hasAreas) {
            $section->addTextBreak(2);
            $section->addText('Incident Areas by Purok',
                ['bold' => true, 'size' => 12], ['spaceAfter' => 100]);

            $areaTable = $section->addTable([
                'borderSize' => 4, 'borderColor' => 'e4e4e7',
                'cellMargin' => 60, 'width' => 100 * 50, 'unit' => 'pct',
            ]);

            $areaTable->addRow(400);
            foreach (['Purok', 'Incident Area', 'Count', 'Share %'] as $h) {
                $areaTable->addCell(null, ['bgColor' => 'f4f4f5'])
                          ->addText($h, ['bold' => true, 'size' => 9, 'color' => '52525b']);
            }

            foreach ($purokStats as $purokName => $data) {
                if ($data['areas']->isEmpty()) continue;
                $areaSum = $data['areas']->sum();
                $first   = true;
                foreach ($data['areas'] as $area => $count) {
                    $pct = $areaSum > 0 ? round($count / $areaSum * 100, 1) : 0;
                    $areaTable->addRow(360);
                    $areaTable->addCell(null, ['bgColor' => 'ffffff'])
                              ->addText($first ? $purokName : '', ['size' => 9, 'bold' => $first]);
                    $areaTable->addCell(null, ['bgColor' => 'ffffff'])
                              ->addText($area, ['size' => 9]);
                    $areaTable->addCell(null, ['bgColor' => 'ffffff'])
                              ->addText((string) $count, ['bold' => true, 'size' => 9],
                                  ['alignment' => 'right']);
                    $areaTable->addCell(null, ['bgColor' => 'ffffff'])
                              ->addText($pct . '%', ['size' => 9, 'color' => '71717a'],
                                  ['alignment' => 'right']);
                    $first = false;
                }
            }
        }
    }

    // Footer
    $section->addTextBreak(1);
    $section->addText(
        'This report was automatically generated by the Barangay Sto. Rosario Services Management System.',
        ['size' => 8, 'color' => 'aaaaaa', 'italic' => true],
        ['alignment' => 'center']
    );

    // ── Save & export ─────────────────────────────────────────────
    $tempDocx = tempnam(sys_get_temp_dir(), 'analytics_') . '.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tempDocx);

    try {
        // Load the generated DOCX using PHPWord
        $phpWordObj = IOFactory::load($tempDocx);
        
        // Save to HTML temporarily
        $htmlWriter = IOFactory::createWriter($phpWordObj, 'HTML');
        $htmlTempFile = tempnam(sys_get_temp_dir(), 'analytics_html_');
        $htmlWriter->save($htmlTempFile);
        
        $htmlContent = file_get_contents($htmlTempFile);
        @unlink($htmlTempFile);

        // Render HTML to PDF via DomPDF
        $pdf = PDF::loadHTML($htmlContent)
            ->setPaper('letter', 'portrait');

        $datePart = match ($filterType) {
            'date'  => \Carbon\Carbon::parse($date)->format('Y-m-d'),
            'month' => \Carbon\Carbon::parse($month . '-01')->format('F_Y'),
            'year'  => $year,
            default => 'All_Time',
        };
        $filename = ($panel === 'blotter' ? 'Blotter' : 'Certificates') . "_Report_{$datePart}.pdf";

        return $pdf->download($filename);
    } catch (\Exception $e) {
        abort(500, 'PDF generation failed: ' . $e->getMessage());
    } finally {
        @unlink($tempDocx);
    }
}

  private function applyDateFilter($query, $filterType, $date, $month, $year, $column)
{
    // ← Must RETURN the query
    return match($filterType) {
        'date'  => $query->whereDate($column, $date),
        'month' => $query->whereYear($column, substr($month, 0, 4))
                         ->whereMonth($column, substr($month, 5, 2)),
        'year'  => $query->whereYear($column, $year),
        default => $query,  // 'all' — no filter
    };
}
}