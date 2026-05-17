<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentsImportTemp extends Model
{
    use HasFactory;

    protected $table = 'residents_import_temp';

    protected $primaryKey = 'temp_id';

    protected $fillable = [
        'first_name_raw',
        'middle_name_raw',
        'last_name_raw',
        'suffix_raw',
        'age_raw',
        'age',
        'sex_raw',
        'address_raw',
        'purok_raw',
        'household_number_raw',
        'birth_date_raw',
        'birth_place_raw',
        'civil_status_raw',
        'relation_to_head_raw',
        'educational_attainment_raw',
        'grade_course_raw',
        'school_raw',
        'profession_occupation_raw',
        'employment_type_raw',
        'contact_number_raw',
        'fb_email_address_raw',
        'phic_no_raw',
        'membership_raw',
        'family_planning_method_raw',
        'sanitary_toilet_raw',
        'water_supply_raw',
        'smoker_raw',
        'binge_drinker_raw',
        'hpn_raw',
        'dm_raw',
        'pwd_raw',
        'pwd_type_raw',
        'mothers_maiden_name_raw',
        'date_registered',
        'import_status',
        'import_error_message',
        'source_file',
        'imported_at',
    ];

    protected $casts = [
        'birth_date_raw' => 'date',
        'date_registered' => 'date',
        'sanitary_toilet_raw' => 'boolean',
        'smoker_raw' => 'boolean',
        'binge_drinker_raw' => 'boolean',
        'hpn_raw' => 'boolean',
        'dm_raw' => 'boolean',
        'pwd_raw' => 'boolean',
        'imported_at' => 'datetime',
    ];

    protected $dates = [
        'birth_date_raw',
        'date_registered',
        'imported_at',
    ];
}
