<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $fillable = ['name', 'description'];

public function blotterRecords()
{
    return $this->belongsToMany(
        BlotterRecord::class,
        'blotter_incident_type',
        'incident_type_id',  // pivot FK → incident_types.id  ✅
        'blotter_id',        // pivot FK → blotter_records.??? 
        'id',                // incident_types local key       ✅
        'blotter_id'         // blotter_records local key — should match $primaryKey
    )->withTimestamps();
}
}