<?php

namespace App\Models;

use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Model;
use App\Models\DairyAnalysisItem;

class DairyAnalysis extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'sales_invoice_id',
        'purchase_invoice_id',
        'sales_rebate_total',
        'sales_premium_total',
        'purchase_rebate_total',
        'purchase_premium_total',
        'payment_status',
        'sales_inv_number'
    ];

    public function details()
    {
        return $this->hasMany(DairyAnalysisItem::class, 'dairy_analysis_id');
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function reference()
    {
        return $this->hasOne(Reference::class, 'source_id', 'purchase_invoice_id')->where('source_type', SourceType::PURCHASE);
    }
}
