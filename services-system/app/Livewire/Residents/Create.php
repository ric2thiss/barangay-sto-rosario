<?php

namespace App\Livewire\Residents;

use App\Models\Purok;
use App\Models\Resident;
use Livewire\Component;

class Create extends Component
{
    public $first_name;

    public $middle_name;

    public $last_name;

    public $suffix;

    public $birth_date;

    public $birth_place;

    public $sex;

    public $civil_status;

    public $relation_to_head;

    public $address;

    public $purok_id;

    public $contact_number;

    public $fb_email_address;

    public $phic_no;

    public $membership;

    public $family_planning_method;

    public $sanitary_toilet;

    public $water_supply;

    public $smoker;

    public $binge_drinker;

    public $hpn;

    public $dm;

    public $pwd;

    public $pwd_type;

    public $mothers_maiden_name;

    public $date_registered;

    public $household_number;

    public $educational_attainment;

    public $grade_course;

    public $school;

    public $profession_occupation;

    public $employment_type;

    public $residency_status;

    public function mount()
    {
        // Allow saving with just basic info by pre-filling defaults
        $this->date_registered = today()->format('Y-m-d');
        $this->residency_status = 'Active';
    }

    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|string|max:255',
            'relation_to_head' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'purok_id' => 'required|exists:puroks,purok_id',
            'contact_number' => 'nullable|string|max:255',
            'fb_email_address' => 'nullable|string|max:255', // Changed to string instead of email
            'phic_no' => 'nullable|string|max:255',
            'membership' => 'nullable|string|max:255',
            'family_planning_method' => 'nullable|string|max:255',
            'sanitary_toilet' => 'nullable|boolean',
            'water_supply' => 'nullable|in:I,II,III',
            'smoker' => 'nullable|boolean',
            'binge_drinker' => 'nullable|boolean',
            'hpn' => 'nullable|boolean',
            'dm' => 'nullable|boolean',
            'pwd' => 'nullable|boolean',
            'pwd_type' => 'nullable|required_if:pwd,true|string|max:255',
            'mothers_maiden_name' => 'nullable|string|max:255',
            'date_registered' => 'required|date',
            'household_number' => 'nullable|string|max:255',
            'educational_attainment' => 'nullable|string|max:255',
            'grade_course' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'profession_occupation' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:PRIVATE,GOVERNMENT',
            'residency_status' => 'required|string|in:Active,Deceased,Moved Out',
        ];
    }

    public function save()
    {
        $this->validate();

        Resident::create([
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'sex' => $this->sex,
            'civil_status' => $this->civil_status,
            'relation_to_head' => $this->relation_to_head,
            'address' => $this->address,
            'purok_id' => $this->purok_id,
            'contact_number' => $this->contact_number,
            'fb_email_address' => $this->fb_email_address,
            'phic_no' => $this->phic_no,
            'membership' => $this->membership,
            'family_planning_method' => $this->family_planning_method,
            'sanitary_toilet' => $this->sanitary_toilet ?? false,
            'water_supply' => $this->water_supply,
            'smoker' => $this->smoker ?? false,
            'binge_drinker' => $this->binge_drinker ?? false,
            'hpn' => $this->hpn ?? false,
            'dm' => $this->dm ?? false,
            'pwd' => $this->pwd ?? false,
            'pwd_type' => $this->pwd_type,
            'mothers_maiden_name' => $this->mothers_maiden_name,
            'date_registered' => $this->date_registered,
            'household_number' => $this->household_number,
            'educational_attainment' => $this->educational_attainment,
            'grade_course' => $this->grade_course,
            'school' => $this->school,
            'profession_occupation' => $this->profession_occupation,
            'employment_type' => $this->employment_type,
            'residency_status' => $this->residency_status,
        ]);

        session()->flash('message', 'Resident successfully created.');

        return redirect()->route('residents.index');
    }

    public function render()
    {
        return view('livewire.residents.create', [
            'puroks' => Purok::all(),
        ]);
    }
}
