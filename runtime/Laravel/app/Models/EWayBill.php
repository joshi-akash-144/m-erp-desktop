<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EWayBill extends Model
{
    protected $fillable = [
        'company_id',
        'sales_invoice_id',
        'e_invoice_id',
        'ewb_no',
        'ewb_date',
        'valid_upto',
        'trans_mode',
        'transporter_name',
        'transporter_id',
        'trans_doc_no',
        'trans_doc_date',
        'vehicle_no',
        'vehicle_type',
        'status',
        'cancel_reason_code',
        'cancel_remark',
        'cancelled_at',
        'environment',
        'created_by',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    const STATUS_ACTIVE    = 'active';
    const STATUS_CANCELLED = 'cancelled';

    const TRANS_MODE_ROAD = '1';
    const TRANS_MODE_RAIL = '2';
    const TRANS_MODE_AIR  = '3';
    const TRANS_MODE_SHIP = '4';

    const VEHICLE_TYPE_REGULAR = 'R';
    const VEHICLE_TYPE_ODC     = 'O';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function eInvoice(): BelongsTo
    {
        return $this->belongsTo(EInvoice::class, 'e_invoice_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
