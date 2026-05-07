<?php

namespace App\Livewire\Certificates;

use App\Models\CertificateRequest;
use App\Models\CertificateType;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'All';
    public $typeFilter = 'All';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'All'],
        'typeFilter' => ['except' => 'All'],
    ];

    public function mount()
    {
        $this->statusFilter = request('statusFilter', 'All');
        $this->typeFilter = request('typeFilter', 'All');
        $this->search = request('search', '');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updateStatus($requestId, $status)
    {
        $request = CertificateRequest::findOrFail($requestId);

        // Basic validation
        if (! in_array($status, ['Pending', 'Processing', 'Released', 'Rejected','Approved'])) {
            return;
        }

        $request->update(['status' => $status]);

        session()->flash('message', "Request marked as {$status}.");
    }

    public function updatePaymentStatus($requestId, $paymentStatus)
    {
        $request = CertificateRequest::findOrFail($requestId);

        if (! in_array($paymentStatus, ['Paid', 'Pending'])) {
            return;
        }

        $request->update(['payment_status' => $paymentStatus]);

        session()->flash('message', "Payment status updated to {$paymentStatus}.");
    }

public string $approvalFilter = '';
public function render()
{
    $requests = CertificateRequest::with(['resident', 'certificateType'])
        ->when($this->statusFilter && $this->statusFilter !== 'All', function ($query) {
            $query->where('status', $this->statusFilter);
        })
        ->when($this->typeFilter && $this->typeFilter !== 'All', function ($query) {
            $query->whereHas('certificateType', function ($q) {
                $q->where('certificate_name', $this->typeFilter);
            });
        })
        ->when($this->approvalFilter === 'pending', function ($query) {
            $query->where('status', 'Pending');
        })
        ->when($this->approvalFilter === 'approved', function ($query) {
            $query->where('status', 'Released');
        })
        ->when($this->search, function ($query) {
            $search = '%' . $this->search . '%';

            // Search purpose and certificate type in the old DB (safe)
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', $search)
                  ->orWhereHas('certificateType', function ($q2) use ($search) {
                      $q2->where('certificate_name', 'like', $search);
                  });
            });

            // Search resident name separately via collected IDs from new DB
            $matchingResidentIds = \App\Models\Resident::query()
                ->where('first_name', 'like', $search)
                ->orWhere('surname',  'like', $search)
                ->pluck('id');

            if ($matchingResidentIds->isNotEmpty()) {
                $query->orWhereIn('resident_id', $matchingResidentIds);
            }
        })
        ->latest()
        ->paginate(10);

    $certificateTypes = CertificateType::orderBy('certificate_name')
        ->pluck('certificate_name')
        ->toArray();

    return view('livewire.certificates.index', [
        'requests'         => $requests,
        'certificateTypes' => $certificateTypes,
    ]);
}
    private function getTemplateForCertificate($certificateName)
    {
        $templateMap = [
            'Barangay Clearance' => 'barangay_clearance',
            'Certificate of Residency' => 'residency',
            'Indigency Certificate' => 'indigent',
            'Good Moral Certificate' => 'goodmoral',
            'Barangay Permit' => 'brgy_permit',
        ];

        return $templateMap[$certificateName] ?? 'residency';
    }
}
