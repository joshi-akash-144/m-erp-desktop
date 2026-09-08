<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldRefMapping extends Model
{
    protected $fillable = ['old_ref_id', 'new_ref_id', 'name', 'company_id'];
}
