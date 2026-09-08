<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BagChallanLabour extends Model
{
    use HasCompanyContext, SoftDeletes;

    protected $table = 'bag_challan_labours';

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'bags',
        'rate',
        'bag_amount',
        'loading_amount',
        'total_amount',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'bags'           => 'decimal:2',
        'rate'           => 'decimal:2',
        'bag_amount'     => 'decimal:2',
        'loading_amount' => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'status'         => 'boolean',
    ];

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

    public function items()
    {
        return $this->hasMany(BagChallanLabourItem::class, 'bag_challan_labour_id', 'id');
    }
}
