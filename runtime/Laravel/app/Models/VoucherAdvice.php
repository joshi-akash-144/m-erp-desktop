<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherAdvice extends Model
{
    protected $table = 'voucher_advices';
    protected $fillable = [
        // 'company_id',
        // 'financial_year_id',
        'voucher_id',
        'advice_type',
        'advice_date',
        'account_id',
        'amount',
        'status',
    ];
    
    public function entries()
    {
        return $this->hasMany(VoucherAdviceEntry::class, 'advice_id');
    }
}
