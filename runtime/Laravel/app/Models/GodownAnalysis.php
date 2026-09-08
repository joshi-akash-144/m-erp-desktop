<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GodownAnalysis extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'grn_id',
        'rebate_total',
        'rebate_percentage',
        'premium_total',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    //Relations
    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function details()
    {
        return $this->hasMany(GodownAnalysisItem::class, 'godown_analysis_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
