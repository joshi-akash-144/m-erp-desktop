<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GodownModule extends Model
{
    use HasFactory, SoftDeletes;

    /* ---------------------------------------------
     |  BAG WEIGHT
    |----------------------------------------------*/
    const BAG_GUNNY_WEIGHT   = 1;      // 1 kg per bag
    const BAG_PLASTIC_WEIGHT = 0.2;    // 0.2 kg per bag
    const PRODUCT_IN = 'in';
    const PRODUCT_OUT = 'out';
    const CYCLE_OPEN = 'open';
    const CYCLE_CLOSE = 'close';


    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'godown_id',
        'godown_unit_id',
        'party_destination_id',
        'grn_id',
        'delivery_challan_id',
        'dairy_po',
        'transporter_id',
        'lr_number',
        'challan_date',
        'in_date',
        'out_date',
        'in_time',
        'out_time',
        'in_out_status',
        'challan_weight',
        'challan_bags',
        'is_manual',
        'is_crossing',
        'is_cycle',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'challan_date' => 'date:Y-m-d',
        'in_date' => 'date:Y-m-d',
        'out_date' => 'date:Y-m-d',
        // 'challan_date' => 'date',
        // 'in_date' => 'date',
        // 'out_date' => 'date',
        'is_manual' => 'boolean',
        'is_crossing' => 'boolean',
    ];

    /* ---------------------------------------------
     | Relationships
     |----------------------------------------------*/

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function partyDestination()
    {
        return $this->belongsTo(Destination::class, 'party_destination_id');
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function godown()
    {
        return $this->belongsTo(Destination::class, 'godown_id');
    }

    public function godownUnit()
    {
        return $this->belongsTo(Godown::class, 'godown_unit_id');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function moisture()
    {
        return $this->hasOne(Moisture::class, 'godown_module_id');
    }
}
