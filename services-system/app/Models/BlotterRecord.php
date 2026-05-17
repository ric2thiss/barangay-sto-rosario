<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlotterRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'blotter_id';

    protected $fillable = [
        'form_number',
        'form_id',
        'complainant_id',
        'respondent_id',
        'incident_type',
        'purpose',
        'incident_details',
        'incident_date',
        'status',
        'recorded_by',
        'form_data',
        'evidence_pic',
        'evidence_link',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'form_data'     => 'array',
        'evidence_pic'  => 'array',
    ];

    public function complainant()
    {
        return $this->belongsTo(Resident::class, 'complainant_id', 'id')
                    ->withDefault(['first_name' => 'Unknown', 'surname' => 'Resident']);
    }

    public function respondent()
    {
        return $this->belongsTo(Resident::class, 'respondent_id', 'id')
                    ->withDefault(['first_name' => 'Unknown', 'surname' => 'Resident']);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }

    // Alias for recordedBy — both are kept for compatibility
    public function recorder()
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by', 'user_id');
    }

    public function summons()
    {
        return $this->hasMany(Summon::class, 'blotter_id', 'blotter_id');
    }

    /**
     * Puroks relationship — pivot now carries area_id (FK) instead of incident_area (string).
     * Load the related IncidentArea via: $blotter->puroks->first()->pivot->incidentArea
     */
    public function puroks()
    {
        return $this->belongsToMany(
            \App\Models\Purok::class,
            'blotter_purok',
            'blotter_id',
            'purok_id',
            'blotter_id',
            'purok_id'
        )->withPivot('area_id')->withTimestamps();
    }

    /**
     * Convenience accessor — returns "Purok Name — Area Name" for single-location records.
     * Falls back gracefully if no area is linked.
     */
    public function getLocationAttribute(): ?string
    {
        $first = $this->puroks->first();
        if (! $first) return null;

        $areaName = null;
        if ($first->pivot->area_id) {
            $areaName = \App\Models\IncidentArea::find($first->pivot->area_id)?->name;
        }

        $parts = array_filter([$first->purok_name, $areaName]);
        return implode(' — ', $parts) ?: null;
    }

    public function incidentTypes()
    {
        return $this->belongsToMany(
            \App\Models\IncidentType::class,
            'blotter_incident_type',
            'blotter_id',
            'incident_type_id',
            'blotter_id',
            'id'
        )->withTimestamps();
    }
}