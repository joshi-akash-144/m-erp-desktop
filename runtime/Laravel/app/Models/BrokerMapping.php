<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerMapping extends Model
{
    protected $fillable = ['old_broker_id', 'new_broker_id', 'name', 'company_id'];
}
