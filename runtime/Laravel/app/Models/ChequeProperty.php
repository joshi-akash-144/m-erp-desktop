<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeProperty extends Model
{
    use HasFactory;

    protected $table = 'cheque_properties';

    protected $fillable = [
        'cheque_master_id',
        'column_value',
        'top',
        'left',
        'width',
        'height',
        'align_text',
        'font_name',
        'font_style',
        'font_size',
    ];

    public function master()
    {
        return $this->belongsTo(ChequeMaster::class, 'cheque_master_id');
    }
}
