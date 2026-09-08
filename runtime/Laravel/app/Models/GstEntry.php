<?php

namespace App\Models;

use App\Enums\GstDocumentType;
use App\Enums\GstItcEligibility;
use App\Enums\GstItcType;
use App\Enums\GstSupplyType;
use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;

class GstEntry extends Model
{
    use HasCompanyContext;

    protected $table = 'gst_entries';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'voucher_id',
        'voucher_transaction_id',
        'account_id',
        'account_name',
        'gstin',
        'voucher_type_id',
        'document_type',
        'invoice_no',
        'invoice_date',
        'is_amendment',
        'original_invoice_no',
        'original_invoice_date',
        'item_id',
        'item_name',
        'hsn_code',
        'uqc',
        'qty',
        'supply_type',
        'report_category',
        'place_of_supply',
        'taxable_amount',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'igst_rate',
        'igst_amount',
        'cess_rate',
        'cess_amount',
        'total_tax_amount',
        'invoice_value',
        'reverse_charge',
        'is_ecommerce',
        'ecommerce_gstin',
        'itc_eligibility',
        'itc_type'
    ];

    protected $casts = [
        'document_type'  => GstDocumentType::class,
        'supply_type'    => GstSupplyType::class,
        'is_amendment'   => 'boolean',
        'reverse_charge' => 'boolean',
        'is_ecommerce'   => 'boolean',
        'itc_eligibility' => GstItcEligibility::class,
        'itc_type'        => GstItcType::class,
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
