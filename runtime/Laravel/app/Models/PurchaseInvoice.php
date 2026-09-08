<?php

namespace App\Models;

use App\Enums\SourceType;
use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
        /* ---------------------------------------------
     |  TAX TYPE
     |----------------------------------------------*/
     const TAX_LOCAL      = 'local';
     const TAX_INTERSTATE = 'interstate';
     
    use HasCompanyContext;
    protected $fillable = [
        'invoice_serial',
        'invoice_number',
        'invoice_date',
        'due_date',
        'show_date',
        'party_bill_date',
        'grn_id',
        'grn_number',
        'grn_serial',
        'file_number',
        'sales_invoice_serial',
        'account_id',
        'tax_type',
        'reference_number',
        'broker_id',
        'vehicle_number',
        // 'purchase_invoice_type_id',
        'remarks',
        'paid_amount',
        'net_amount',
        'payment_status',
        'invoice_status',
        'voucher_id',
        'grand_total',
        'created_by',
        'updated_by',
        'financial_year_id',
        'company_id',
        'uuid',
        'purchase_type_id',
        'tax_amount',
        'taxable_amount',
        'total_quantity',
        'rebate_from_analysis'
        // 'net_total'
    ];

    public function details()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id', 'id');
    }

    public function billSundries()
    {
        return $this->hasMany(PurchaseInvoiceSundry::class, 'purchase_invoice_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function purchaseType()
    {
        return $this->belongsTo(PurchaseType::class, 'purchase_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function reference()
    {
        return $this->hasOne(Reference::class, 'voucher_id', 'voucher_id')->where('source_type', SourceType::PURCHASE);
    }
    
}