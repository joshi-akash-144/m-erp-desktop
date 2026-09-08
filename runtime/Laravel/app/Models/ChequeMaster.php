<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeMaster extends Model
{
    use HasFactory;

    protected $table = 'cheque_masters';

    protected $fillable = [
        'uuid',
        'code',
        'formate_name',
        'top_margin',
        'left_margin',
        'cheque_height',
        'cheque_width',
        'is_default',
        'status',
        'company_id',
        'created_by',
        'updated_by',
    ];


    public function properties()
    {
        return $this->hasMany(ChequeProperty::class, 'cheque_master_id');
    }
    public static function nextCode(): int
    {
        // Fetch max code, adapted for ChequeMaster (no soft deletes or company_id columns exist on this table)
        $maxCode = self::selectRaw('MAX(CAST(code AS UNSIGNED)) as max_code')->value('max_code');
        
        if (!$maxCode) {
            return 1000;
        }
        
        if (!$maxCode) {
            throw new \Exception("Error In Code Generation.");
        }

        return (int) $maxCode + 1;
    }
}
