<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    const PurchaseInvoice = 'purchase_invoice';
    const SalesInvoice = 'sales_invoice';
    const Receipt = 'receipt';
    const Payment = 'payment';
    const Journal = 'journal';
    const CreditNote = 'credit_note';
    const DebitNote = 'debit_note';
    const PURCHASE_RETURN = 'purchase_return';
    const SALES_RETURN = 'sales_return';

    const NewReference = 'new_ref';
    const AgainstReference = 'against_ref';
    const Advance = 'advance';
    const OnAccount = 'on_account';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'account_id',
        'reference_number',
        'reference_date',
        'file_number',
        'reference_type',
        'amount',
        'settled_amount',
        'pending_amount',
        'is_hold',
        'is_closed',
        'closed_at',
        'voucher_id',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'status',
        'direction',
        'purchase_order_id',
        'purchase_order_number',
    ];

    // Details of the reference items
    public function details()
    {
        return $this->hasOne(ReferenceItem::class);
    }

    // 🔹 COMPANY
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // 🔹 FINANCIAL YEAR
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    // 🔹 ACCOUNT
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // 🔹 VOUCHER
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // 🔹 USER (created by)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔹 USER (updated by)
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 🔹 USER (deleted by)
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // 🔹 POLYMORPHIC SOURCE (sales_invoice, purchase_invoice, receipt, payment, etc.)
    public function source()
    {
        return $this->morphTo();
    }

    public function allocations()
    {
        return $this->hasMany(ReferenceAllocation::class, 'reference_id');
    }

    // 🔹 PURCHASE ORDER
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
