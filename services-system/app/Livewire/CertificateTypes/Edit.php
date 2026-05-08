<?php

namespace App\Livewire\CertificateTypes;

use App\Models\CertificateType;
use Livewire\Component;

class Edit extends Component
{
    public CertificateType $certificateType;

    public $certificate_name;

    public $price;

    public function mount(CertificateType $certificateType)
    {
        $this->certificateType = $certificateType;
        $this->certificate_name = $certificateType->certificate_name;
        $this->price = $certificateType->price;
    }

    protected $rules = [
        'certificate_name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
    ];

    public function save()
    {
        $this->validate();

        $this->certificateType->update([
            'certificate_name' => $this->certificate_name,
            'price' => $this->price,
        ]);

        session()->flash('message', 'Certificate type successfully updated.');

        return redirect()->route('certificate-types.index');
    }

    public function render()
    {
        return view('livewire.certificate-types.edit');
    }
}
