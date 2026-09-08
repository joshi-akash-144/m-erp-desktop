<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyMaster extends Model
{

    protected $fillable = [
        'party_code',
        'name',
        'gst_no',
        'pan_no',
        'mobile',
    ];

    protected static function booted()
    {
        static::creating(function ($party) {
            $last = self::orderBy('party_code', 'desc')->first();
            $lastCode = $last ? (int)substr($last->party_code, 4) : 0;
            $newCode = 'P' . str_pad($lastCode + 1, 5, '0', STR_PAD_LEFT);
            $party->party_code = $newCode;
        });
    }

    public function details()
    {
        return $this->hasMany(PartyMasterDetail::class, 'party_master_id', 'id');
    }
}
