<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultiGrn extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'financial_year_id',
        'multi_grn_import_date',
        'inward_no',
        'material_doc_no',
        'truck_inward_date',
        'p_o_no',
        'truck_no',
        'material_desc',
        'vendor_name',
        'gross_wt',
        'tare_wt',
        'nt_wt_with_bag',
        'nt_wt_wo_bag',
        'no_of_bag',
        'av_wt_bag',
        'plant',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
