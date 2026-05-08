<?php

namespace App\Livewire\Residents;

use App\Models\Purok;
use App\Models\Resident;
use Livewire\Component;

class Edit extends Component
{
    public Resident $resident;

    public $delete_password = '';

    public $confirm_delete = false;

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

    public function mount(Resident $resident)
    {
        $this->resident = $resident;

        $this->first_name = $resident->first_name;
        $this->middle_name = $resident->middle_name;
        $this->last_name = $resident->last_name;
        $this->suffix = $resident->suffix;
        $this->birth_date = optional($resident->birth_date)->format('Y-m-d');
        $this->birth_place = $resident->birth_place;
        $this->sex = $resident->sex;
        $this->civil_status = $resident->civil_status;
        $this->relation_to_head = $resident->relation_to_head;
        $this->address = $resident->address;
        $this->purok_id = $resident->purok_id;
        $this->contact_number = $resident->contact_number;
        $this->fb_email_address = $resident->fb_email_address;
        $this->phic_no = $resident->phic_no;
        $this->membership = $resident->membership;
        $this->family_planning_method = $resident->family_planning_method;
        $this->sanitary_toilet = is_null($resident->sanitary_toilet) ? '' : (string) (int) $resident->sanitary_toilet;
        $this->water_supply = $resident->water_supply;
        $this->smoker = is_null($resident->smoker) ? '' : (string) (int) $resident->smoker;
        $this->binge_drinker = is_null($resident->binge_drinker) ? '' : (string) (int) $resident->binge_drinker;
        $this->hpn = is_null($resident->hpn) ? '' : (string) (int) $resident->hpn;
        $this->dm = is_null($resident->dm) ? '' : (string) (int) $resident->dm;
        $this->pwd = is_null($resident->pwd) ? '' : (string) (int) $resident->pwd;
        $this->pwd_type = $resident->pwd_type;
        $this->mothers_maiden_name = $resident->mothers_maiden_name;
        $this->date_registered = optional($resident->date_registered)->format('Y-m-d');
        $this->household_number = $resident->household_number;
        $this->educational_attainment = $resident->educational_attainment;
        $this->grade_course = $resident->grade_course;
        $this->school = $resident->school;
        $this->profession_occupation = $resident->profession_occupation;
        $this->employment_type = $resident->employment_type;
        $this->residency_status = $resident->residency_status;
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
            'pwd_type' => 'nullable|required_if:pwd,1|string|max:255',
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

    private function normalizeBoolInput($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if ($value === true || $value === 1 || $value === '1') {
            return 1;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 0;
        }
        return null;
    }

    private function normalizeStringInput($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return is_string($value) ? trim($value) : $value;
    }

    private function normalizeDateInput($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return $value;
    }

    public function save()
    {
        $this->validate();

        $this->resident->update([
            'first_name' => $this->first_name,
            'middle_name' => $this->normalizeStringInput($this->middle_name),
            'last_name' => $this->last_name,
            'suffix' => $this->normalizeStringInput($this->suffix),
            'birth_date' => $this->normalizeDateInput($this->birth_date),
            'birth_place' => $this->normalizeStringInput($this->birth_place),
            'sex' => $this->sex,
            'civil_status' => $this->civil_status,
            'relation_to_head' => $this->normalizeStringInput($this->relation_to_head),
            'address' => $this->normalizeStringInput($this->address),
            'purok_id' => $this->purok_id,
            'contact_number' => $this->normalizeStringInput($this->contact_number),
            'fb_email_address' => $this->normalizeStringInput($this->fb_email_address),
            'phic_no' => $this->normalizeStringInput($this->phic_no),
            'membership' => $this->normalizeStringInput($this->membership),
            'family_planning_method' => $this->normalizeStringInput($this->family_planning_method),
            'sanitary_toilet' => $this->normalizeBoolInput($this->sanitary_toilet),
            'water_supply' => $this->normalizeStringInput($this->water_supply),
            'smoker' => $this->normalizeBoolInput($this->smoker),
            'binge_drinker' => $this->normalizeBoolInput($this->binge_drinker),
            'hpn' => $this->normalizeBoolInput($this->hpn),
            'dm' => $this->normalizeBoolInput($this->dm),
            'pwd' => $this->normalizeBoolInput($this->pwd),
            'pwd_type' => $this->normalizeStringInput($this->pwd_type),
            'mothers_maiden_name' => $this->normalizeStringInput($this->mothers_maiden_name),
            'date_registered' => $this->normalizeDateInput($this->date_registered),
            'household_number' => $this->normalizeStringInput($this->household_number),
            'educational_attainment' => $this->normalizeStringInput($this->educational_attainment),
            'grade_course' => $this->normalizeStringInput($this->grade_course),
            'school' => $this->normalizeStringInput($this->school),
            'profession_occupation' => $this->normalizeStringInput($this->profession_occupation),
            'employment_type' => $this->normalizeStringInput($this->employment_type),
            'residency_status' => $this->residency_status,
        ]);

        session()->flash('message', 'Resident successfully updated.');

        return redirect()->route('residents.index');
    }

    public function render()
    {
        return view('livewire.residents.edit', [
            'puroks' => Purok::all(),
        ]);
    }

    public function showDeleteConfirm()
    {
        $this->resetErrorBag();
        $this->confirm_delete = true;
        $this->delete_password = '';
    }

    public function cancelDeleteConfirm()
    {
        $this->resetErrorBag();
        $this->confirm_delete = false;
        $this->delete_password = '';
    }

    public function deleteResident()
    {
        $this->validate([
            'delete_password' => 'required|string',
        ]);

        if (! \Hash::check($this->delete_password, auth()->user()->password)) {
            $this->addError('delete_password', 'Invalid password.');

            return;
        }

        $this->resident->delete();

        session()->flash('message', 'Resident deleted successfully.');

        return redirect()->route('residents.index');
    }
}
