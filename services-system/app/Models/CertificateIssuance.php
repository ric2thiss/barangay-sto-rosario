<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateIssuance extends Model
{
    use HasFactory;

    protected $table = 'certificate_requests';

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'resident_id',
        'certificate_type_id',
        'purpose',
        'status',
        'payment_status',
        'amount_due',
        'bir_tax',
        'requested_by',
        'processed_by',
        'date_requested',
        'date_released',
    ];

    protected $casts = [
        'date_requested' => 'date',
        'date_released' => 'date',
        'amount_due' => 'decimal:2',
        'bir_tax' => 'decimal:2',
    ];

public function resident()
{
    return $this->belongsTo(Resident::class, 'resident_id', 'id')
                ->withDefault([
                    'first_name' => 'Unknown',
                    'surname'    => 'Resident',
                    'full_name'  => 'Unknown Resident',
                ]);
}

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id', 'certificate_type_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by', 'user_id');
    }

    public function printLogs()
    {
        return $this->hasMany(PrintLog::class, 'certificate_request_id', 'request_id');
    }
}
