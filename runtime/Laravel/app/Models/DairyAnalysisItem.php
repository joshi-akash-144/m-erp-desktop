<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DairyAnalysisItem extends Model
{
    protected $fillable = [
        'dairy_analysis_id',
        'element_id',
        'guarantee',
        'actual',
        'diff',
        'rebate_percentage',
        'sales_rebate',
        'sales_premium',
        'purchase_rebate',
        'purchase_premium',
    ];

    public function master()
    {
        return $this->belongsTo(DairyAnalysis::class, 'dairy_analysis_id');
    }

    public function element()
    {
        return $this->belongsTo(Element::class, 'element_id');
    }
}
