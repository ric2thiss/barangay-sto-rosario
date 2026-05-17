<?php

namespace App\Livewire\Residents;

use App\Models\Resident;
use Livewire\Component;

class Show extends Component
{
    public Resident $resident;

    public function mount(Resident $resident)
    {
        $this->resident = $resident;
    }

    public function render()
    {
        return view('livewire.residents.show');
    }
}
