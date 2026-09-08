<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use App\Traits\HasCompanyContext;

class Transporter extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'gstin',
        'pan_no',
        'contact_person',
        'mobile',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
   
}
