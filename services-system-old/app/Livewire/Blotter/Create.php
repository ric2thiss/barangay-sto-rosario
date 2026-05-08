<?php

namespace App\Livewire\Blotter;

use App\Models\BlotterRecord;
use App\Models\Resident;
use Livewire\Component;

class Create extends Component
{
    public $complainant_id;

    public $respondent_id;

    public $incident_type;

    public $incident_details;

    public $incident_date;

    public $status = 'Active';

    public $searchComplainant = '';

    public $searchRespondent = '';

    protected $rules = [
        'complainant_id' => 'required|exists:residents,resident_id',
        'respondent_id' => 'required|exists:residents,resident_id',
        'incident_type' => 'required|string|max:255',
        'incident_details' => 'required|string',
        'incident_date' => 'required|date',
        'status' => 'required|in:Active,Settled,Dismissed,Pending',
    ];

    public function mount()
    {
        $this->incident_date = now()->format('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        BlotterRecord::create([
            'complainant_id' => $this->complainant_id,
            'respondent_id' => $this->respondent_id,
            'incident_type' => $this->incident_type,
            'incident_details' => $this->incident_details,
            'incident_date' => $this->incident_date,
            'status' => $this->status,
            'recorded_by' => auth()->user()->user_id,
        ]);

        session()->flash('message', 'Blotter record successfully created.');

        return redirect()->route('blotter.index');
    }

}