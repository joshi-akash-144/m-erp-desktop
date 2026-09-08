<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'sales_invoice_id',
        'irn',
        'ack_no',
        'ack_dt',
        'doc_type',
        'doc_no',
        'doc_date',
        'signed_qr_code',
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

    const DOC_TYPE_INVOICE = 'INV';
    const DOC_TYPE_CREDIT  = 'CRN';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function eWayBills(): HasMany
    {
        return $this->hasMany(EWayBill::class, 'e_invoice_id');
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
