<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\CertificateIssuance;
use App\Models\CertificateRequest;
use App\Models\DeathRecord;
use App\Models\IndigentRecord;
use App\Models\PrintLog;
use App\Models\Purok;
use App\Models\Resident;
use App\Models\ResidentsImportTemp;
use App\Models\Summon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summary cards
     */
    public function index()
    {
        // Total residents
        $totalResidents = Resident::count();

        // Total seniors (age >= 60)
        $totalSeniors = Resident::whereNotNull('birth_date')
            ->get()
            ->filter(function ($resident) {
                return $resident->age >= 60;
            })->count();

        // Total PWDs (use residents.pwd flag)
        $totalPwd = Resident::where('pwd', true)->count();

        // Residents per Purok
        $residentsPerPurok = Purok::withCount('residents')
            ->orderBy('purok_name')
            ->get();

        return view('dashboard', compact(
            'totalResidents',
            'totalSeniors',
            'totalPwd',
            'residentsPerPurok'
        ));
    }

    /**
     * Delete all residents and related data for testing purposes
     */
    public function deleteAllResidents(Request $request)
    {
        // Only allow in local environment or with special confirmation
        if (app()->environment('production') && $request->input('confirm_delete') !== 'DELETE_ALL_RESIDENTS_CONFIRM') {
            abort(403, 'Access denied');
        }

        DB::transaction(function () {
            // Delete related records first (to respect foreign key constraints)
            DeathRecord::truncate();
            IndigentRecord::truncate();
            PrintLog::truncate();
            CertificateIssuance::truncate();
            CertificateRequest::truncate();
            BlotterRecord::truncate();
            Summon::truncate();

            // Finally delete residents
            Resident::truncate();

            // Also clear temp import table if exists
            if (DB::getSchemaBuilder()->hasTable('residents_import_temp')) {
                ResidentsImportTemp::truncate();
            }
        });

        return redirect()->back()->with('message', 'All residents and related data have been deleted successfully.');
    }
}
