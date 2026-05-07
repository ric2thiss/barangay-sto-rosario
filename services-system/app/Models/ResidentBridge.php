<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentBridge extends Model
{
    use HasFactory;

    protected $connection = 'sto_rosario';
    protected $table      = 'residents';
    protected $primaryKey = 'id';

    protected $appends = [
        'resident_id',   // makes $resident->resident_id work
        'last_name',     // maps surname → last_name
        'full_name',
        'birth_date',    // maps birthdate → birth_date
    ];

    // ── Accessor aliases ──────────────────────────────────────────
    // Expose new PK as resident_id so old code doesn't break
    public function getResidentIdAttribute(): int
    {
        return $this->id;
    }

    // surname → last_name
    public function getLastNameAttribute(): ?string
    {
        return $this->surname;
    }

    // birthdate → birth_date
    public function getBirthDateAttribute(): ?string
    {
        return $this->birthdate;
    }

    // Computed full name
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->surname,
            $this->suffix,
        ]);

        return implode(' ', $parts);
    }

    // Age from birthdate
    public function getAgeAttribute(): ?int
    {
        return $this->birthdate
            ? \Carbon\Carbon::parse($this->birthdate)->age
            : null;
    }

    // ── Relationships (cross-DB, pointing to old DB tables) ───────
    public function certificateRequests()
    {
        // Cross-DB: join on resident_id = id
        return $this->hasMany(CertificateRequest::class, 'resident_id', 'id');
    }

    public function blotterComplainant()
    {
        return $this->hasMany(BlotterRecord::class, 'complainant_id', 'id');
    }

    public function blotterRespondent()
    {
        return $this->hasMany(BlotterRecord::class, 'respondent_id', 'id');
    }

    public function certificateIssuances()
    {
        return $this->hasMany(CertificateIssuance::class, 'resident_id', 'id');
    }

    // ── Scopes (mirror what Livewire Create uses) ─────────────────
    public function scopeSearch($query, string $term)
    {
        return $query
            ->where('first_name', 'like', "%{$term}%")
            ->orWhere('surname',  'like', "%{$term}%");
    }

    // Soft-delete filter (new DB uses is_deleted flag, not SoftDeletes trait)
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }
}