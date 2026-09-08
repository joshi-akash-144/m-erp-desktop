<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DeliveryChallan extends Model
{
    use HasCompanyContext, SoftDeletes;

    /* ---------------------------------------------
     |  STATUS CONSTANTS
     |----------------------------------------------*/
    const STATUS_OPEN     = 'open';
    const STATUS_HOLD     = 'hold';
    const STATUS_CLOSE    = 'close';
    const STATUS_BILLED   = 'billed';
    const STATUS_CANCEL   = 'cancel';
    const STATUS_REJECTED = 'rejected';

    /* ---------------------------------------------
     |  QC CONSTANTS
     |----------------------------------------------*/
    const QC_PENDING = 'pending';
    const QC_PASSED  = 'passed';
    const QC_FAILED  = 'failed';

    /* ---------------------------------------------
     |  TAX TYPE
     |----------------------------------------------*/
    const TAX_LOCAL      = 'local';
    const TAX_INTERSTATE = 'interstate';

    /* ---------------------------------------------
     |  BAG TYPE
     |----------------------------------------------*/
    const BAG_PLASTIC = 'plastic';
    const BAG_GUNNY   = 'gunny';

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'challan_serial',
        'challan_number',
        'account_id',
        'broker_id',
        'challan_date',
        'challan_in_date',
        'challan_out_date',
        'gst_type',
        'reference_number',
        'vehicle_number',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'bag_type',
        'bag_count',
        'net_weight_wt_bag',
        'total_quantity',
        'sub_total',
        'total_tax',
        'is_qc_required',
        'qc_status',
        'remarks',
        'challan_status',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'challan_date'     => 'date',
        'challan_in_date'  => 'date',
        'challan_out_date' => 'date',
        'gross_weight'     => 'decimal:2',
        'tare_weight'      => 'decimal:2',
        'net_weight'       => 'decimal:2',
        'net_weight_wt_bag'=> 'decimal:2',
        'total_quantity'   => 'decimal:4',
        'sub_total'        => 'decimal:2',
        'total_tax'        => 'decimal:2',
        'is_qc_required'   => 'boolean',
        'status'           => 'boolean',
    ];

    protected function challanDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function challanInDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function challanOutDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_id');
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

    public function details()
    {
        return $this->hasMany(DeliveryChallanItem::class, 'delivery_challan_id', 'id');
    }
}
