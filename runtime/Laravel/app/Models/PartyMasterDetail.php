<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyMasterDetail extends Model
{
    protected $fillable = [
        'party_master_id',
        'company_id',
        'account_id',
    ];

    public function master()
    {
        return $this->belongsTo(PartyMaster::class, 'party_master_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
