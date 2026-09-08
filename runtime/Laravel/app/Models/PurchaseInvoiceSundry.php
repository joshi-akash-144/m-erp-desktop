<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceSundry extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'sundry_id',
        "code",
        "name",
        "bill_sundry_type",
        "calculation_type",
        "apply_on",
        "bill_sundry_modal_dr_id",
        "bill_sundry_modal_cr_id",
        "base_amount",
        "rate_percent",
        "value",
        "amount",
        "sort_order",
        'affect_net_total',
        "remarks",
        'account_id'
    ];

    public function crAccount()
    {
        return $this->belongsTo(Account::class, 'bill_sundry_modal_cr_id');
    }

    public function drAccount()
    {
        return $this->belongsTo(Account::class, 'bill_sundry_modal_dr_id');
    }
}
