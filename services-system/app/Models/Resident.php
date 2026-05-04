<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Resident extends Model
{
    use HasFactory, LogsActivity;

    // ── Connection & table ────────────────────────────────────────
    protected $connection  = 'sto_rosario';
    protected $table       = 'residents';
    protected $primaryKey  = 'id';
    public    $incrementing = true;
    protected $keyType     = 'int';

    protected $fillable = [
        'first_name',
        'middle_name',
        'surname',
        'suffix',
        'birthdate',
        'birthplace',
        'age',
        'sex',
        'lgbtq_identity',
        'lgbtq_other_text',
        'civil_status',
        'nationality',
        'religion',
        'ethnicity',
        'blood_type',
        'philhealth_no',
        'length_of_residency',
        'house_ownership',
        'house_material',
        'toilet_type',
        'water_source',
        'is_4ps',
        'is_nhts',
        'is_solo_parent',
        'is_smoker',
        'is_binge_drinker',
        'has_hypertension',
        'has_diabetes',
        'has_asthma',
        'has_tb',
        'has_cancer',
        'has_mental_health',
        'membership_type',
        'family_planning',
        'is_pwd',
        'pwd_type',
        'pwd_id_no',
        'is_deceased',
        'date_of_death',
        'is_newborn',
        'purok',
        'household_no',
        'barangay',
        'municipality',
        'province',
        'household_position',
        'total_household',
        'voters_status',
        'educational_attainment',
        'grade_level',
        'school_name',
        'course',
        'contact_no',
        'email',
        'occupation_type',
        'occupation',
        'monthly_income',
        'annual_income',
        'socioeconomic_status',
        'image_path',
        'is_deleted',
        'is_purok_president',
        'username',
        'account_status',
        'user_role',
    ];

    protected $casts = [
        'birthdate'      => 'date',
        'date_of_death'  => 'date',
        'graduation_date'=> 'date',
        'monthly_income' => 'decimal:2',
        'annual_income'  => 'decimal:2',
        'is_deleted'     => 'boolean',
        'is_purok_president' => 'boolean',
    ];

    // ── Global scope: alias surname → last_name for backwards compat ──
    protected static function booted(): void
    {
        static::addGlobalScope('surname_alias', function ($builder) {
            $builder->select('*', DB::raw('surname as last_name'));
        });

        // Exclude soft-deleted records by default
        static::addGlobalScope('active', function ($builder) {
            $builder->where('is_deleted', 0);
        });
    }

    // ── Accessors ─────────────────────────────────────────────────

    // Makes $resident->resident_id work everywhere in old code
    public function getResidentIdAttribute(): int
    {
        return $this->id;
    }

  

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->surname,
            $this->suffix,
        ])));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate
            ? \Carbon\Carbon::parse($this->birthdate)->age
            : null;
    }

    // ── Relationships (all use 'id' as local key now) ─────────────

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class, 'resident_id', 'id');
    }

    public function certificateIssuances()
    {
        return $this->hasMany(CertificateIssuance::class, 'resident_id', 'id');
    }

    public function blotterComplainant()
    {
        return $this->hasMany(BlotterRecord::class, 'complainant_id', 'id');
    }

    public function blotterRespondent()
    {
        return $this->hasMany(BlotterRecord::class, 'respondent_id', 'id');
    }
}