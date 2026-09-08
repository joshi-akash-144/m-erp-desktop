<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GodownAnalysisItem extends Model
{
    protected $fillable = [
        'godown_analysis_id',
        'element_id',
        'guarantee',
        'actual',
        'difference',
        'rebate',
        'rebate_percentage',
        'premium',
    ];

    //Relations
    public function godownAnalysis()
    {
        return $this->belongsTo(GodownAnalysis::class);
    }

    public function element()
    {
        return $this->belongsTo(Element::class);
    }
}
