<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    use HasFactory;

    protected $primaryKey = 'certificate_type_id';

    protected $fillable = [
        'certificate_name',
        'price',
    ];

    public function layouts()
    {
        return $this->hasMany(CertificateLayout::class, 'certificate_type_id', 'certificate_type_id');
    }

    public function issuances()
    {
        return $this->hasMany(CertificateIssuance::class, 'certificate_type_id', 'certificate_type_id');
    }
}
