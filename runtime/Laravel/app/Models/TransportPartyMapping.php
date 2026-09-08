<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPartyMapping extends Model
{
        protected $fillable = [ 'company_id', 'name','old_id','new_id','account_type'];

}
