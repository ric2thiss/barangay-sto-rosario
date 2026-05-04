<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateLayout extends Model
{
    use HasFactory;

    protected $primaryKey = 'layout_id';

    protected $fillable = [
        'certificate_type_id',
        'layout_name',
        'content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id', 'certificate_type_id');
    }
}
