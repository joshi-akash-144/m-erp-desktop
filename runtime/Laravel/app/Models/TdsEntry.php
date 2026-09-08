<?php

namespace App\Models;

use App\Enums\TdsEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TdsEntry extends Model
{
    use SoftDeletes;

    protected $table = 'tds_entries';

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'voucher_id',
        'voucher_transaction_id',
        'account_id',
        'tds_category_id',
        'reference_no',
        'deductee_name',
        'pan_no',
        'payment_amount',
        'payment_date',
        'tds_rate',
        'tds_amount',
        'total_deducted',
        'tax_deducted_on',
        'section_code',
        'voucher_type_id',
        'is_lower_deduction',
        'certificate_no',
        'remarks',
    ];

    protected $casts = [
        'entry_type'        => TdsEntryType::class,
        'is_lower_deduction' => 'boolean',
        'payment_amount'    => 'decimal:2',
        'tds_rate'          => 'decimal:4',
        'tds_amount'        => 'decimal:2',
        'total_deducted'    => 'decimal:2',
        'payment_date'      => 'date',
        'tax_deducted_on'   => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function tdsCategory()
    {
        return $this->belongsTo(TdsCategory::class);
    }
    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }
}
