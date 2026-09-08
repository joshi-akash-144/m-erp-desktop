<?php

namespace App\Models;

use App\Enums\SourceType;
use App\Traits\HasCompanyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use HasCompanyContext, SoftDeletes;

    // Delivery days default
    const DEFAULT_DELIVERY_DAYS = 15;

    const STATUS_OPEN = 'open';
    const STATUS_CLOSE = 'close';

    // Tax types
    const TAX_LOCAL       = 'local';
    const TAX_INTERSTATE  = 'interstate';
    const GST_TYPE        = [self::TAX_LOCAL, self::TAX_INTERSTATE];

    // IRN Status Constants
    const IRN_STATUS_PENDING   = 'pending';
    const IRN_STATUS_GENERATED = 'generated';
    const IRN_STATUS_FAILED    = 'failed';
    const IRN_STATUS_CANCELLED = 'cancelled';

    // EWB Status Constants
    const EWB_STATUS_PENDING   = 'pending';
    const EWB_STATUS_GENERATED = 'generated';
    const EWB_STATUS_FAILED    = 'failed';
    const EWB_STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'invoice_serial',
        'invoice_number',
        'invoice_date',
        'delivery_challan_number',
        'sales_order_id',
        'sales_order_serial',
        'grn_number',
        'reference_number',
        'account_id',
        'last_invoice_date',
        // 'broker_id',
        'kms',
        'vehicle_number',
        // 'party_bill_date',
        'sale_type_id',
        'delivery_date',
        'total_quantity',
        'net_amount',
        'total_amount',
        'received_amount',
        'taxable_amount',
        'tax_amount',
        'grand_total',
        // 'ewaybill_number',
        // 'invoice_status',
        'voucher_id',
        // 'ewaybill_status',
        'remarks',
        // 'payment_received_status',
        'gst_type',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function latestBillDate($companyId = null, $financialYearId = null)
    {
        $query = static::query();
        if ($companyId) {
            $query->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId);
        }
        $date = $query
            ->orderByDesc('invoice_date')
            ->value('invoice_date');

        return $date
            ? Carbon::parse($date)
            : null;
    }

    /* -----------------------------------------
     | Relationships
     |------------------------------------------
     */

    public function details()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id', 'id');
    }

    public function billSundries()
    {
        return $this->hasMany(SalesInvoiceSundry::class, 'sales_invoice_id', 'id');
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

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function items()
    {
        return $this->hasManyThrough(
            Item::class,
            SalesInvoiceItem::class,
            'sales_invoice_id',
            'id',
            'id',
            'item_id'
        );
    }
    public function sales_invoice_details()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id', 'id');
    }

    // Company belongs to a country
    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }


    public function dairyAnalysis()
    {
        return $this->hasOne(DairyAnalysis::class, 'sales_invoice_id', 'id');
    }

    public function linkedPurchaseInvoice()
    {
        return $this->hasOne(PurchaseInvoice::class, 'sales_invoice_serial', 'invoice_serial');
    }

    public function linkedPurchaseInvoiceSundries()
    {
        return $this->hasManyThrough(
            PurchaseInvoiceSundry::class,
            PurchaseInvoice::class,
            'sales_invoice_serial',
            'purchase_invoice_id', 
            'invoice_serial',       
            'id'                   
        )->where('purchase_invoice_sundries.code', 1008);         
    }

    public function eWayBill()
    {
        return $this->hasOne(EWayBill::class, 'sales_invoice_id', 'id')->where('status','active');
    }

    public function eInvoice()
    {
        return $this->hasOne(EInvoice::class, 'sales_invoice_id', 'id')->where('status','active');
    }

    public function receipt()
    {
        return $this->hasOne(SalesInvoiceReceipt::class, 'sales_invoice_id', 'id');
    }

    public function reference()
    {
        return $this->hasOne(Reference::class, 'voucher_id', 'voucher_id')->where('source_type', SourceType::SALES);
    }
}
