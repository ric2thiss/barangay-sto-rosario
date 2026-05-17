<?php

use App\Http\Controllers\PurokController;
use App\Http\Middleware\EnsureUserIsOfAge; // ← add this
use App\Livewire\Certificate\ResidencyForm;
use App\Livewire\Certificates\Create as CertificatesCreate;
use App\Livewire\Certificates\Index as CertificatesIndex;
use App\Livewire\CertificateTypes\Create as CertificateTypesCreate;
use App\Livewire\CertificateTypes\Edit as CertificateTypesEdit;
use App\Livewire\CertificateTypes\Index as CertificateTypesIndex;
use App\Livewire\Residents\Create as ResidentsCreate;
use App\Livewire\Residents\Edit as ResidentsEdit;
use App\Livewire\Residents\Import as ResidentsImport;
use App\Livewire\Residents\Index as ResidentsIndex;
use App\Livewire\Residents\Show as ResidentsShow;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Resident_viewsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ResidentTestController;
use App\Http\Controllers\IncidentAreaController;
use App\Http\Controllers\SsoController;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/test-residents', [ResidentTestController::class, 'index']);
// Shared by all roles
Route::middleware(['auth', 'verified'])->group(function () {



    Route::get('certificate-types', CertificateTypesIndex::class)->name('certificate-types.index');
    Route::get('certificate-types/create', CertificateTypesCreate::class)->name('certificate-types.create');
    Route::get('certificates', CertificatesIndex::class)->name('certificates.index');
    Route::get('certificates/barangay-clearance', function () {
        return redirect()->route('certificates.index', ['typeFilter' => 'Barangay Clearance']);
    })->name('certificates.barangay-clearance');
    Route::get('certificates/barangay-permit', function () {
        return redirect()->route('certificates.index', ['typeFilter' => 'Barangay Permit']);
    })->name('certificates.barangay-permit');
    Route::get('certificates/certificate-of-residency', function () {
        return redirect()->route('certificates.index', ['typeFilter' => 'Certificate of Residency']);
    })->name('certificates.certificate-of-residency');
    Route::get('certificates/good-moral-certificate', function () {
        return redirect()->route('certificates.index', ['typeFilter' => 'Good Moral Certificate']);
    })->name('certificates.good-moral-certificate');
    Route::get('certificates/indigency-certificate', function () {
        return redirect()->route('certificates.index', ['typeFilter' => 'Indigency Certificate']);
    })->name('certificates.indigency-certificate');

Route::get('certificates/create', CertificatesCreate::class)
    ->name('certificates.create')
    ->middleware(['auth', EnsureUserIsOfAge::class]);
    Route::get('certificate/residency', ResidencyForm::class)->name('certificate.residency');
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // SSO: redirect to treasury-system with auto-login token
    Route::get('sso/treasury', [SsoController::class, 'redirectToTreasury'])->name('sso.treasury');
});


// Admin & Secretary only

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::resource('users', AdminUserController::class)->except(['show']);
});
Route::middleware(['auth', 'verified', 'role:Admin,Secretary'])->group(function () {
    Route::get('residents', ResidentsIndex::class)->name('residents.index');
    Route::get('residents/create', ResidentsCreate::class)->name('residents.create');
    Route::get('residents/import', ResidentsImport::class)->name('residents.import');
   Route::get('residents/download-template', [App\Http\Controllers\ResidentsTemplateController::class, 'downloadTemplate'])->name('residents.download-template');
    Route::post('residents/ajax-upload', [App\Http\Controllers\ResidentsTemplateController::class, 'ajaxUpload'])->name('residents.ajax-upload');
    Route::post('residents/commit-all', [App\Http\Controllers\ResidentsImportController::class, 'commitAll'])->name('residents.commit-all');
     Route::get('residents/{resident}/edit', ResidentsEdit::class)->name('residents.edit');
    Route::get('residents/{resident}', ResidentsShow::class)->name('residents.show');


Route::resource('incident-areas', IncidentAreaController::class)
    ->only(['index', 'store', 'update', 'destroy']);

    Route::post('puroks', [IncidentAreaController::class, 'storePurok'])->name('puroks.store');
Route::put('puroks/{purok}', [IncidentAreaController::class, 'updatePurok'])->name('puroks.update');
Route::delete('puroks/{purok}', [IncidentAreaController::class, 'destroyPurok'])->name('puroks.destroy');

    // web.php — inside your auth middleware group
Route::get('analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
Route::get('analytics/export', [App\Http\Controllers\AnalyticsController::class, 'exportReport'])->name('analytics.export');
    Route::get('certificate-types/{certificateType}/edit', CertificateTypesEdit::class)->name('certificate-types.edit');
    
    Route::get('blotter/create', [BlotterController::class, 'create'])->name('blotter.create');

    Route::get('certificates/{id}/{format}/{template}', [App\Http\Controllers\CertificateExportController::class, 'export'])->name('certificates.export');
    Route::get('blotter', [BlotterController::class, 'index'])->name('blotter.index');
    Route::get('blotter/create', [BlotterController::class, 'create'])->name('blotter.create');
    Route::get('blotter/residents/search', [BlotterController::class, 'searchResidents'])->name('blotter.residents.search');
   
    Route::post('blotter', [BlotterController::class, 'store'])->name('blotter.store');
    Route::get('blotter/{blotter}', [BlotterController::class, 'show'])->name('blotter.show');
    Route::get('blotter/{blotter}/edit', [BlotterController::class, 'edit'])->name('blotter.edit');
    Route::put('blotter/{blotter}', [BlotterController::class, 'update'])->name('blotter.update');
    Route::delete('blotter/{blotter}', [BlotterController::class, 'destroy'])->name('blotter.destroy');
    Route::get('blotter/{blotter}/download-docx', [BlotterController::class, 'downloadDocx'])->name('blotter.download-docx');
    Route::get('blotter/{blotter}/download-pdf', [BlotterController::class, 'downloadPdf'])->name('blotter.download-pdf');
    Route::get('blotter/{blotterId}/{format}/{template}', [BlotterController::class, 'exportForm'])->name('blotter.export');
    Route::patch('blotter/{blotter}/status', [BlotterController::class, 'updateStatus'])->name('blotter.updateStatus');

Route::get('/blotter-test', [AnalyticsController::class, 'blotterTest'])->name('blotter.test');


    // Route::get('puroks/create', [PurokController::class, 'create'])->name('puroks.create');
    // Route::post('puroks', [PurokController::class, 'store'])->name('puroks.store');
    // Route::get('puroks/{purok}', [PurokController::class, 'show'])->name('puroks.show');
    // Route::delete('puroks/{purok}', [PurokController::class, 'destroy'])->name('puroks.destroy');
    // Route::get('puroks/{purok}/export/consolidation', [PurokController::class, 'exportConsolidation'])->name('puroks.export.consolidation');
    // Route::get('puroks/{purok}/export/residents', [PurokController::class, 'exportResidents'])->name('puroks.export.residents');

    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('reports/export/residents', [ReportsController::class, 'exportResidents'])->name('reports.export.residents');
    Route::get('reports/export/certificates', [ReportsController::class, 'exportCertificates'])->name('reports.export.certificates');
    Route::get('reports/export/blotters', [ReportsController::class, 'exportBlotters'])->name('reports.export.blotters');

    Route::get('dashboard', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('dashboard');
    Route::post('dashboard/delete-all-residents', [DashboardController::class, 'deleteAllResidents'])->name('dashboard.delete-all-residents');
});


// Resident only
Route::middleware(['auth', 'verified', 'role:Resident'])->group(function () {
    Route::get('certificate_reports', [Resident_viewsController::class, 'Certificates_index'])->name('certificates.resident_index');
    Route::get('blotter_reports', [Resident_viewsController::class, 'Blotter_index'])->name('blotter.resident_index');
    Route::get('blotter_reports/residents/search', [Resident_viewsController::class, 'searchResidents'])->name('blotter_reports.residents.search');
    Route::get('blotter_reports/create', [Resident_viewsController::class, 'create'])->name('blotter_reports.resident_create'); // ← before {blotter}
    Route::get('blotter_reports/{blotter}', [Resident_viewsController::class, 'Blotter_show'])->name('blotter.resident_show');
    Route::post('blotter_reports', [Resident_viewsController::class, 'store'])->name('blotter_reports.store');
    Route::get('blotter_reports/{blotter}/edit', [Resident_viewsController::class, 'resident_edit'])->name('blotter.resident_edit');
    Route::put('blotter_reports/{blotter}', [Resident_viewsController::class, 'resident_update'])->name('blotter.resident_update');
});


require __DIR__.'/auth.php';
