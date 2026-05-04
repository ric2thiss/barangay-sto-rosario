<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndigentRecord extends Model
{
    use HasFactory;

    protected $primaryKey = 'indigent_id';

    protected $fillable = [
        'resident_id',
        'remarks',
        'approved_by',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }
}
