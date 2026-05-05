<?php

namespace App\Livewire\Certificates;

use App\Models\CertificateRequest;
use App\Models\CertificateType;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public $resident_id;

    public $certificate_type_id;

    public $purpose;

    public $date_requested;

    public $status = 'Pending';

    public $payment_status = 'Pending';

    public $amount = '0.00';

    public $bir_tax = '30.00';

    public $total_amount = '30.00';

    public $isFreeCertificate = false;

    public $selectedResidentName = '';

    public function rules()
    {
        return [
            'resident_id' => "required|exists:sto_rosario.residents,id",
            'certificate_type_id' => 'required|exists:certificate_types,certificate_type_id',
            'purpose' => 'required|string|max:255',
            'date_requested' => 'required|date',
            'status' => 'required|in:Pending,Processing,Released,Rejected',
        ];
    }

    public $searchResident = '';

    public $selectedResident = null;

    public function mount()
    {
        $this->date_requested = now()->format('Y-m-d');
        $this->updateTotalAmount();
    }

    public function save()
    {
        $rules = $this->rules();
        if (!auth()->user()?->hasRole('Resident')) {
            $rules['payment_status'] = 'required|in:Paid,Pending';
        }

        $this->validate($rules);

        $certificateType = CertificateType::find($this->certificate_type_id);
        $resident = Resident::find($this->resident_id);

        // Keep user-entered amount for non-free certificates; only force zero values for indigency.
        if ($certificateType && $this->isIndigencyCertificate($certificateType)) {
            $this->isFreeCertificate = true;
            $this->payment_status = 'Paid';
            $this->amount = '0.00';
            $this->bir_tax = '0.00';
        } else {
            $this->isFreeCertificate = false;
            if ($this->parseMoney($this->bir_tax) <= 0) {
                $this->bir_tax = '30.00';
            }
        }

        $this->updateTotalAmount();
        $totalAmount = $this->parseMoney($this->amount) + $this->parseMoney($this->bir_tax);

        $request = CertificateRequest::create([
            'resident_id' => $this->resident_id,
            'certificate_type_id' => $this->certificate_type_id,
            'purpose' => $this->purpose,
            'date_requested' => $this->date_requested,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'amount_due' => $totalAmount,
            'bir_tax' => $this->parseMoney($this->bir_tax),
            'requested_by' => auth()->user()->name ?? 'System',
        ]);

        $residentName = trim(implode(' ', array_filter([
            $resident?->first_name,
            $resident?->middle_name,
            $resident?->surname,
        ])));

        DB::connection('treasury')->table('payment_status')->insert([
            'resident_id' => $this->resident_id,
            'certificate_type' => $certificateType?->certificate_name ?? 'Unknown',
            'purpose' => $this->purpose,
            'resident_fname' => $residentName,
            'amount' => $totalAmount,
            'bir_tax' => $this->parseMoney($this->bir_tax),
            'payment_status' => $this->payment_status,
            'created_at' => now(),
        ]);

        session()->flash('message', 'Certificate request successfully created.');

        session(['auto_download_request_id' => $request->request_id]);

        $indexRoute = auth()->user()->hasRole('Resident')
            ? 'certificates.resident_index'
            : 'certificates.index';

        return redirect()->route($indexRoute);
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
    public function render()
    {
        $residents = Resident::query()
            // No ->select() here — let the model's getLastNameAttribute() accessor handle it
            ->when($this->searchResident, function ($query) {
                $query->where('first_name', 'like', '%' . $this->searchResident . '%')
                    ->orWhere('surname', 'like', '%' . $this->searchResident . '%');
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->limit(50)
            ->get();

        return view('livewire.certificates.create', [
            'residents' => $residents,
            'certificateTypes' => CertificateType::orderBy('certificate_name')->get(),
            'isPendingOnly' => auth()->user()->hasRole('Resident'),
        ]);
    }

    public function updatedResidentId($value)
    {
        if ($value) {
            $this->selectedResident = Resident::find($value);
            // Store name as plain string immediately — survives Livewire serialization
            $this->selectedResidentName = $this->selectedResident
                ? trim(implode(', ', array_filter([
                    $this->selectedResident->surname,
                    trim($this->selectedResident->first_name . ' ' . $this->selectedResident->middle_name),
                ])))
                : '';
            $this->searchResident = '';
        } else {
            $this->selectedResident = null;
            $this->selectedResidentName = '';
        }
    }

    public function updatedCertificateTypeId($value)
    {
        if (!$value) {
            $this->isFreeCertificate = false;
            $this->payment_status = 'Pending';
            $this->amount = '0.00';
            $this->bir_tax = '30.00';
            $this->updateTotalAmount();
            return;
        }

        $certificateType = CertificateType::find($value);
        $this->applyCertificatePricing($certificateType);
    }

    public function updatedAmount($value)
    {
        $this->updateTotalAmount();
    }

    private function updateTotalAmount(): void
    {
        $amount = $this->parseMoney($this->amount);
        $birTax = $this->parseMoney($this->bir_tax);
        $this->total_amount = number_format($amount + $birTax, 2, '.', '');
    }

    private function parseMoney($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0.0;
        }

        return (float) $clean;
    }

    private function applyCertificatePricing(?CertificateType $certificateType): void
    {
        if (!$certificateType) {
            return;
        }

        if ($this->isIndigencyCertificate($certificateType)) {
            $this->isFreeCertificate = true;
            $this->payment_status = 'Paid';
            $this->amount = '0.00';
            $this->bir_tax = '0.00';
            $this->updateTotalAmount();

            return;
        }

        $this->isFreeCertificate = false;
        $this->amount = number_format((float) ($certificateType->price ?? 0), 2, '.', '');

        if ($this->parseMoney($this->bir_tax) <= 0) {
            $this->bir_tax = '30.00';
        }

        $this->updateTotalAmount();
    }

    private function isIndigencyCertificate(CertificateType $certificateType): bool
    {
        $name = Str::lower((string) $certificateType->certificate_name);

        return Str::contains($name, 'indigency') || Str::contains($name, 'indigent');
    }

    public function clearResident()
    {
        $this->resident_id = null;
        $this->selectedResident = null;
        $this->selectedResidentName = '';
        $this->searchResident = '';
    }
}
