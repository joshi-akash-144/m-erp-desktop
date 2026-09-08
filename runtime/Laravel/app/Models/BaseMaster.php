<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseMaster extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status' => 'boolean',
    ];

    // /**
    //  * Boot method to define global scope and audit events
    //  */
    // protected static function booted()
    // {
    //     static::addGlobalScope('active', function (Builder $builder) {
    //         $builder->where('status', true);
    //     });
    // }

    // /**
    //  * Scope to include inactive records if needed
    //  */
    // public function scopeWithInactive($query)
    // {
    //     return $query->withoutGlobalScope('active');
    // }

     /**
     * Scope for filtering active/inactive records.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $status ('active' | 'inactive' | null)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus(Builder $query, ?string $status = null): Builder
    {
        return match ($status) {
            'active'   => $query->where('status', true),
            'inactive' => $query->where('status', false),
            default    => $query, // no filter, return all
        };
    }

    /**
     * Helper methods
     */
    public function activate()
    {
        $this->status = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->status = false;
        $this->save();
    }

    /**
     * ==========================
     * Basic Relationships
     * ==========================
     */

    // Creator user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Updater user
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }


}
