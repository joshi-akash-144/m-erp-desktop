<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceReceipt extends Model
{
    use HasCompanyContext;

    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_invoice_id',
        'received_by',
        'received_at',
        'remarks',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function customer()
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
