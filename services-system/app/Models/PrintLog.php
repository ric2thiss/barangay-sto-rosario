<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'print_id';

    protected $fillable = [
        'certificate_request_id',
        'printed_by',
        'print_date',
    ];

    protected $casts = [
        'print_date' => 'datetime',
    ];

    public function certificateIssuance()
    {
        return $this->belongsTo(CertificateIssuance::class, 'certificate_request_id', 'request_id');
    }

    public function printedBy()
    {
        return $this->belongsTo(User::class, 'printed_by', 'user_id');
    }
}
