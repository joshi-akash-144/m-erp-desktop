<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMapping extends Model
{
    protected $fillable = ['old_item_id', 'new_item_id', 'name', 'company_id'];
}
