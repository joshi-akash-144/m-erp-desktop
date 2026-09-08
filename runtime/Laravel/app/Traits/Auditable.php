<?php

namespace App\Traits;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        // Track creation
        static::created(function ($model) {
            $model->auditLog('CREATE', $model->toArray(), null);
        });

        // Track updates
        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();
            
            // Remove timestamps from changes
            unset($changes['updated_at']);
            
            if (!empty($changes)) {
                $model->auditLog('UPDATE', $changes, $original);
            }
        });

        // Track deletions
        static::deleted(function ($model) {
            $model->auditLog('DELETE', null, $model->toArray());
        });
    }

    protected function auditLog($type, $newValues, $oldValues)
    {
        $user = Auth::user();
        
        if (!$user) return;

        $changedFields = $type === 'UPDATE' 
            ? array_keys($newValues ?? []) 
            : null;

        AuditTrail::create([
            'company_id' => company_id(),
            'financial_year_id' => financial_year_id(),
            'audit_type' => $type,
            'module' => $this->getModule(),
            'table_name' => $this->getTable(),
            'record_id' => $this->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'description' => $this->getAuditDescription($type),
            'status' => 'success',
            'performed_at' => now(),
        ]);
    }

    protected function getModule()
    {
        // Override in model to specify module
        return strtoupper(class_basename($this));
    }

    protected function getAuditDescription($type)
    {
        $module = $this->getModule();
        return "{$type} operation on {$module} record";
    }

    // Manual audit logging for custom actions
    public function logCustomAudit($type, $description, $data = null)
    {
        $user = Auth::user();
        
        if (!$user) return;

        AuditTrail::create([
            'company_id' => company_id(),
            'financial_year_id' => financial_year_id(),
            'audit_type' => $type,
            'module' => $this->getModule(),
            'table_name' => $this->getTable(),
            'record_id' => $this->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'new_values' => $data,
            'description' => $description,
            'status' => 'success',
            'performed_at' => now(),
        ]);
    }
}
