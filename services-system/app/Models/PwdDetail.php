<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PwdDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'pwd_id';

    protected $fillable = [
        'resident_id',
        'pwd_id_number',
        'disability_type',
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
