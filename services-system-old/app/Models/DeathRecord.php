<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeathRecord extends Model
{
    use HasFactory;

    protected $primaryKey = 'death_id';

    protected $fillable = [
        'resident_id',
        'date_of_death',
        'cause_of_death',
        'place_of_death',
        'recorded_by',
    ];

    protected $casts = [
        'date_of_death' => 'date',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }
}
