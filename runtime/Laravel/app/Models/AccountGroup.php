<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCompanyContext;

class AccountGroup  extends BaseMaster
{
    use HasCompanyContext;

    public const TYPE = ['asset', 'liability', 'income', 'expense'];

    protected $fillable = ['uuid','name','code','type','parent_id','f_v','is_active', 'is_party_group', 'updated_by', 'created_by','deleted_by'];


    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountGroup::class, 'parent_id');
    }

    public function accountCodeSequence()
    {
        return $this->hasOne(AccountCodeSequence::class, 'account_group_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class, 'account_group_id');
    }
}
