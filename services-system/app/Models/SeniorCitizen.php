<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeniorCitizen extends Model
{
    use HasFactory;

    protected $primaryKey = 'senior_id';

    protected $fillable = [
        'resident_id',
        'senior_id_number',
        'date_registered',
    ];

    protected $casts = [
        'date_registered' => 'date',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }
}
