<?php

namespace App\Livewire\CertificateTypes;

use App\Models\CertificateType;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $certificateTypes = CertificateType::query()
            ->when($this->search, function ($query) {
                $query->where('certificate_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.certificate-types.index', [
            'certificateTypes' => $certificateTypes,
        ]);
    }
}
