<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTaxDetail extends Model
{
    use SoftDeletes;

    public const TAX_DETAIL = ['registered', 'unregistered', 'composition', 'uni_holder'];
    public const FILING_FREQUENCY = ['not_known', 'monthly', 'quarterly'];
    public const ITC_ELIGIBILITY = ['input_goods', 'input_service', 'capital_goods'];
    public const RCM_NATURE = ['based_on_daily_limit', 'compulsory', 'service_import'];
    public const GST_STATUS = ['active', 'inactive', 'cancelled', 'pending'];
    public const TAX_TYPE = ['igst', 'cgst', 'sgst', 'professional_tax'];
    public const GST_TYPE = ['gst_applicable', 'gst_not_applicable', 'non_gst'];
    

    protected $fillable = [
        'account_id', 'type_of_dealer', 'filing_frequency', 'gst_number',
        'tax_category_id', 'hsn_sac_code', 'itc_eligibility', 'rcm_nature','pan','tin','tax_type','gst_type',
        'tds_category_id', 'payee_category_id'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function taxCategory()
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category');
    }

    public function tdsCategory()
    {
        return $this->belongsTo(TdsCategory::class, 'tds_category_id');
    }

    public function payeeCategory()
    {
        return $this->belongsTo(PayeeCategory::class, 'payee_category_id');
    }
}
