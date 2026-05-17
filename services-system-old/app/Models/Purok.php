<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Purok extends Model
{
    use HasFactory;

    protected $primaryKey = 'purok_id';

    protected $fillable = [
        'purok_name',
    ];

    public function residents()
    {
        return $this->hasMany(Resident::class, 'purok_id', 'purok_id');
    }

    public function blotterRecords()
    {
        return $this->belongsToMany(
            \App\Models\BlotterRecord::class,
            'blotter_purok',
            'purok_id',
            'blotter_id',
            'purok_id',
            'blotter_id'
        )->withPivot('area_id')->withTimestamps();
    }

    /**
     * Get all IncidentArea models used in blotters for this purok.
     * Use this to populate a dropdown when creating/editing blotter records.
     */
    public function usedIncidentAreas()
    {
        $areaIds = DB::table('blotter_purok')
            ->where('purok_id', $this->purok_id)
            ->whereNotNull('area_id')
            ->distinct()
            ->pluck('area_id');

        return IncidentArea::whereIn('id', $areaIds)->orderBy('name')->get();
    }

    /**
     * @deprecated Use usedIncidentAreas() instead.
     * Kept for backward compatibility in any views still calling usedAreas().
     */
    public function usedAreas(): array
    {
        return $this->usedIncidentAreas()->pluck('name')->toArray();
    }
}