<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
trait HasCompanyContext
{
    public static function bootHasCompanyContext()
    {
        static::creating(function ($model) {
            // Company
            if (session()->has('company_id') && empty($model->company_id)) {
                $model->company_id = session('company_id');
            }


            // Created By
            if (isset($model->created_by) && Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (isset($model->updated_by) && Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (isset($model->deleted_by) && Auth::check()) {
                $model->deleted_by = Auth::id();
                if(isset($model->is_active)){ 
                    $model->is_active = false;
                }
                $model->saveQuietly(); // avoid recursion
            }
        });
    }
}
