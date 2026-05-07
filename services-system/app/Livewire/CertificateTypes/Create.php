<?php

namespace App\Livewire\CertificateTypes;

use App\Models\CertificateType;
use Livewire\Component;

class Create extends Component
{
    public $certificate_name;

    public $price;

    protected $rules = [
        'certificate_name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
    ];

    public function save()
    {
        $this->validate();

        CertificateType::create([
            'certificate_name' => $this->certificate_name,
            'price' => $this->price,
        ]);

        session()->flash('message', 'Certificate type successfully created.');

        return redirect()->route('certificate-types.index');
    }

    public function render()
    {
        return view('livewire.certificate-types.create');
    }
}
