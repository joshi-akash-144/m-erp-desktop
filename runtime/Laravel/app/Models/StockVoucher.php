<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;

class StockVoucher extends Model
{
    use HasCompanyContext;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'voucher_date',
        'voucher_id',
        'voucher_type_id',
        'voucher_number',
        'voucher_serial',
        'reference_number',
        'status'
    ];

    public function transactions()
    {
        return $this->hasMany(StockVoucherTransaction::class);
    }
    public function purchaseInvoice(){
        return $this->belongsTo(PurchaseInvoice::class,'voucher_id','voucher_id');
    }
    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class,'voucher_type_id','id');
    }
    public function salesInvoice(){
        return $this->belongsTo(SalesInvoice::class,'voucher_id','voucher_id');
    }

}
