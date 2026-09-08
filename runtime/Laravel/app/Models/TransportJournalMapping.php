<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportJournalMapping extends Model
{
    protected $fillable = [ 'company_id','old_voucher_no','new_voucher_no','old_table_id'];
}
