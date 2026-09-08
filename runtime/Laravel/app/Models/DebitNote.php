<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use HasCompanyContext, SoftDeletes;

    const STATUS_OPEN   = 'open';
    const STATUS_CLOSED = 'closed';

    const TAX_LOCAL      = 'local';
    const TAX_INTERSTATE = 'interstate';

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'debit_note_serial',
        'debit_note_number',
        'debit_note_date',
        'purchase_invoice_id',
        'purchase_invoice_serial',
        'account_id',
        'purchase_type_id',
        'gst_type',
        'reference_number',
        'ref_no',
        'total_quantity',
        'taxable_amount',
        'tax_amount',
        'net_amount',
        'grand_total',
        'voucher_id',
        'remarks',
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
        $date = $query->orderByDesc('debit_note_date')->value('debit_note_date');

        return $date ? Carbon::parse($date) : null;
    }

    public function details()
    {
        return $this->hasMany(DebitNoteItem::class, 'debit_note_id');
    }

    public function billSundries()
    {
        return $this->hasMany(DebitNoteSundry::class, 'debit_note_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function purchaseType()
    {
        return $this->belongsTo(PurchaseType::class, 'purchase_type_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
