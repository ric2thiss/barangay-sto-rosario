<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    use LogsActivity;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'resident_id',
        'certificate_type_id',
        'purpose',
        'status',
        'payment_status',
        'amount_due',
        'bir_tax',
        'date_requested',
        'requested_by',
    ];

    protected $casts = [
        'date_requested' => 'date',
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
}
