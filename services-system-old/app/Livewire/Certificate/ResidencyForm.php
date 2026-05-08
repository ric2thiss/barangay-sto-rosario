<?php

namespace App\Livewire\Certificate;

use App\Models\CertificateRequest;
use App\Models\CertificateType;
use App\Models\Resident;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ResidencyForm extends Component
{
    public $resident_id = '';

    public $purpose = '';

    public $searchResident = '';

    public function mount()
    {
        // Set default purpose for residency certificate
        $this->purpose = 'For various purposes';
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'resident_id' => ['required', 'exists:residents,resident_id'],
            'purpose' => ['required', 'string', 'max:255'],
        ]);

        // Check if there's a 'Barangay Residency' certificate type
        $certificateType = CertificateType::where('certificate_name', 'LIKE', '%Residency%')->first();

        if (! $certificateType) {
            // Create a default residency certificate type if it doesn't exist
            $certificateType = CertificateType::create([
                'certificate_name' => 'Barangay Residency',
                'description' => 'Certificate of Residency',
                'price' => 0, // Free for now
            ]);
        }

        // Create a certificate request
        CertificateRequest::create([
            'resident_id' => $this->resident_id,
            'certificate_type_id' => $certificateType->certificate_type_id,
            'purpose' => $this->purpose,
            'date_requested' => now(),
            'status' => 'Released', // Mark as released immediately for direct generation
            'requested_by' => Auth::check() ? Auth::user()->name : 'System',
        ]);

        session()->flash('message', 'Residency certificate generated successfully.');

        $this->reset(['resident_id', 'purpose']);
    }

    public function render()
    {
        $residents = Resident::query()
            ->when($this->searchResident, function ($query) {
                $query->where('first_name', 'like', '%'.$this->searchResident.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchResident.'%');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(50) // Limit results for performance
            ->get();

        return view('livewire.certificate.residency-form', [
            'residents' => $residents,
        ]);
    }
}
