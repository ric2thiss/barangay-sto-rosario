<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summon extends Model
{
    use HasFactory;

    protected $primaryKey = 'summon_id';

    protected $fillable = [
        'blotter_id',
        'respondent_id',
        'summon_date',
        'hearing_date',
        'remarks',
    ];

    protected $casts = [
        'summon_date' => 'date',
        'hearing_date' => 'date',
    ];

    public function blotterRecord()
    {
        return $this->belongsTo(BlotterRecord::class, 'blotter_id', 'blotter_id');
    }

    public function respondent()
    {
        return $this->belongsTo(Resident::class, 'respondent_id', 'resident_id');
    }
}
