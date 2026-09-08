<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class TransportParty extends BaseMaster
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'uuid',
        'company_id',
        'name',
        'state_id',
        'city',
        'postal_code',
        'mobile_number',
        'gst_number',
        'address_one',
        'address_two',
        'email',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
