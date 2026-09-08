<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrailDetail extends Model
{

    const SECTION_MASTER = 1;
    const SECTION_VOUCHER = 2;
    const SECTION_VOUCHER_DETAILS = 3;
    const SECTION_BILL_SUNDRIES = 4;
    const SECTION_INVOICE_ENTRIES = 5;
    const SECTION_ITEM_ENTRIES = 6;


    protected $fillable = [
        'audit_trail_id',
        'section',
        'field_name',
        'key_name',
        'old_value',
        'new_value',
        'visible'
    ];

    public function auditTrail()
    {
        return $this->belongsTo(AuditTrail::class);
    }
}
