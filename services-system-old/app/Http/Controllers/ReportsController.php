<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\CertificateIssuance;
use App\Models\Purok;
use App\Models\Resident;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display the reports dashboard
     */
    public function index()
    {
        // Calculate various statistics for the reports
        $totalResidents = Resident::count();
        $maleResidents = Resident::where('sex', 'Male')->count();
        $femaleResidents = Resident::where('sex', 'Female')->count();

        // Residents by age group
        $residentsByAge = [
            'children' => Resident::whereBetween('age', [0, 17])->count(),
            'adults' => Resident::whereBetween('age', [18, 59])->count(),
            'seniors' => Resident::where('age', '>=', 60)->count(),
        ];

        // Residents by civil status
        $residentsByCivilStatus = [
            'single' => Resident::where('civil_status', 'Single')->count(),
            'married' => Resident::where('civil_status', 'Married')->count(),
            'widowed' => Resident::where('civil_status', 'Widowed')->count(),
            'divorced' => Resident::where('civil_status', 'Divorced')->count(),
            'separated' => Resident::where('civil_status', 'Separated')->count(),
        ];

        // Residents per Purok
        $residentsPerPurok = Purok::withCount('residents')->orderBy('purok_name')->get();

        // Recent certificate issuances
        $recentCertificates = CertificateIssuance::with('certificateType', 'resident')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent blotter records
        $recentBlotters = BlotterRecord::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly registrations (last 6 months)
        $monthlyRegistrations = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $registrations = Resident::whereMonth('date_registered', $month->month)
                ->whereYear('date_registered', $month->year)
                ->count();

            $monthlyRegistrations[] = [
                'month' => $month->format('M Y'),
                'count' => $registrations,
            ];
        }

        return view('reports.index', compact(
            'totalResidents',
            'maleResidents',
            'femaleResidents',
            'residentsByAge',
            'residentsByCivilStatus',
            'residentsPerPurok',
            'recentCertificates',
            'recentBlotters',
            'monthlyRegistrations'
        ));
    }

    /**
     * Export residents data as CSV
     */
    public function exportResidents()
    {
        $residents = Resident::with('purok')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="residents_report_'.date('Y-m-d_H-i-s').'.csv"',
        ];

        $callback = function () use ($residents) {
            $handle = fopen('php://output', 'wb');

            // Column headers
            fputcsv($handle, [
                'ID',
                'First Name',
                'Middle Name',
                'Last Name',
                'Suffix',
                'Sex',
                'Age',
                'Birth Date',
                'Birth Place',
                'Civil Status',
                'Address',
                'Purok',
                'Contact Number',
                'Email/Facebook',
                'Date Registered',
                'Residency Status',
            ]);

            // Data rows
            foreach ($residents as $resident) {
                fputcsv($handle, [
                    $resident->resident_id,
                    $resident->first_name,
                    $resident->middle_name,
                    $resident->last_name,
                    $resident->suffix,
                    $resident->sex,
                    $resident->age,
                    $resident->birth_date ? $resident->birth_date->format('Y-m-d') : '',
                    $resident->birth_place,
                    $resident->civil_status,
                    $resident->address,
                    $resident->purok ? $resident->purok->purok_name : '',
                    $resident->contact_number,
                    $resident->fb_email_address,
                    $resident->date_registered ? $resident->date_registered->format('Y-m-d') : '',
                    $resident->residency_status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export certificates data as CSV
     */
    public function exportCertificates()
    {
        $certificates = CertificateIssuance::with('certificateType', 'resident')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="certificates_report_'.date('Y-m-d_H-i-s').'.csv"',
        ];

        $callback = function () use ($certificates) {
            $handle = fopen('php://output', 'wb');

            // Column headers
            fputcsv($handle, [
                'ID',
                'Certificate Type',
                'Resident Name',
                'Purpose',
                'Date Issued',
                'Issued By',
                'Amount Paid',
                'OR Number',
            ]);

            // Data rows
            foreach ($certificates as $certificate) {
                fputcsv($handle, [
                    $certificate->issuance_id,
                    $certificate->certificateType ? $certificate->certificateType->type_name : '',
                    $certificate->resident ? $certificate->resident->full_name : '',
                    $certificate->purpose,
                    $certificate->created_at->format('Y-m-d H:i:s'),
                    $certificate->issued_by,
                    $certificate->amount_paid,
                    $certificate->or_number,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export blotter records as CSV
     */
    public function exportBlotters()
    {
        $blotters = BlotterRecord::with('complainantResident', 'respondentResident')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="blotters_report_'.date('Y-m-d_H-i-s').'.csv"',
        ];

        $callback = function () use ($blotters) {
            $handle = fopen('php://output', 'wb');

            // Column headers
            fputcsv($handle, [
                'ID',
                'Case Number',
                'Complainant',
                'Respondent',
                'Nature of Complaint',
                'Date Reported',
                'Status',
                'Location of Incident',
            ]);

            // Data rows
            foreach ($blotters as $blotter) {
                fputcsv($handle, [
                    $blotter->blotter_id,
                    $blotter->case_number,
                    $blotter->complainantResident ? $blotter->complainantResident->full_name : $blotter->complainant_name,
                    $blotter->respondentResident ? $blotter->respondentResident->full_name : $blotter->respondent_name,
                    $blotter->nature_of_complaint,
                    $blotter->created_at->format('Y-m-d H:i:s'),
                    $blotter->status,
                    $blotter->location_of_incident,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
