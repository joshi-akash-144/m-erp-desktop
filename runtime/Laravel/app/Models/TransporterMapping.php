<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransporterMapping extends Model
{
    protected $fillable = ['old_transporter_id', 'new_transporter_id', 'name', 'company_id'];
}
