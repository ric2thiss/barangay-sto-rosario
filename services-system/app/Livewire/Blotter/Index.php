<?php

namespace App\Livewire\Blotter;

use App\Models\BlotterRecord;
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
        $records = BlotterRecord::with(['complainant', 'respondent', 'recordedBy'])
            ->when($this->search, function ($query) {
                $query->whereHas('complainant', function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%');
                })
                    ->orWhereHas('respondent', function ($q) {
                        $q->where('first_name', 'like', '%'.$this->search.'%')
                            ->orWhere('last_name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhere('incident_type', 'like', '%'.$this->search.'%')
                    ->orWhere('incident_details', 'like', '%'.$this->search.'%');
            })
            ->orderByRaw("CASE status WHEN 'Pending' THEN 0 WHEN 'Active' THEN 1 ELSE 2 END")
            ->latest('created_at')
            ->paginate(10);

        return view('livewire.blotter.index', [
            'records' => $records,
        ]);
    }
}
