<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;



class Grn extends Model
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

    /* ---------------------------------------------
     |  BAG WEIGHT
     |----------------------------------------------*/
    const BAG_GUNNY_WEIGHT   = 1;      // 1 kg per bag
    const BAG_PLASTIC_WEIGHT = 0.2;    // 0.2 kg per bag


    /* ---------------------------------------------
     |  ENTRY FROM CONSTANTS
     |----------------------------------------------*/
    const ENTRY_FROM_GODOWN = 'godown';
    const ENTRY_FROM_MOBILE = 'mobile';
    const ENTRY_FROM_OFFICE = 'office';

    /* ---------------------------------------------
     |  Table, Fillable
     |----------------------------------------------*/
    protected $table = 'grns';

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'grn_serial',
        'grn_number',
        'account_id',
        'party_type',
        'broker_id',
        'purchase_bill_id',
        'contract_number',
        'grn_date',
        'grn_in_date',
        'grn_out_date',
        'gst_type',
        'reference_number',
        'vehicle_number',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'bag_type',
        'bag_count',
        'party_bill_date',
        'url_path',
        'entry_from',
        'net_weight_wt_bag',
        'total_quantity',
        'sub_total',
        'total_tax',
        'is_bag_entry',
        'is_qc_required',
        'qc_status',
        'remarks',
        'grn_status',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_skip_serial_generation',
        'penalty',
    ];

    /* ---------------------------------------------
     |  Casts
     |----------------------------------------------*/
    protected $casts = [
        'grn_date'        => 'date',
        'grn_in_date'     => 'date',
        'grn_out_date'    => 'date',
        // 'party_bill_date' => 'date',
        'gross_weight'    => 'decimal:2',
        'tare_weight'     => 'decimal:2',
        'net_weight'      => 'decimal:2',
        'total_quantity'  => 'decimal:4',
        'sub_total'       => 'decimal:2',
        'total_tax'       => 'decimal:2',
        'penalty'         => 'decimal:2',
        'is_qc_required'  => 'boolean',
        'status'          => 'boolean',
    ];

    protected function grnDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function grnInDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function grnOutDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    /* ---------------------------------------------
     |  Relationships
     |----------------------------------------------*/


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

    public function purchaseBill()
    {
        // return $this->belongsTo(PurchaseBill::class);
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
        return $this->hasMany(GrnItem::class, 'grn_id', 'id');
    }

    public function godownModule()
    {
        return $this->hasOne(GodownModule::class, 'grn_id', 'id');
    }

    public function moisture()
    {
        return $this->hasOne(Moisture::class, 'grn_id', 'id');
    }
}
