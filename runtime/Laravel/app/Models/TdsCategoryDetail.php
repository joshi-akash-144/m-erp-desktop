<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsCategoryDetail extends Model
{
    protected $table = 'tds_category_details';
    
    protected $fillable = [
        'tds_category_id',
        'payee_category_id',
        'threshold_limit',
        'tds_with_pan',
        'tds_without_pan',
    ];

    public function tdsCategory()
    {
        return $this->belongsTo(TdsCategory::class);
    }

    public function payeeCategory()
    {
        return $this->belongsTo(PayeeCategory::class);
    }
}
