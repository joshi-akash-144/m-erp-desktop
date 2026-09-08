<?php

namespace App\Repositories;

use App\Models\AccountGroup;
use App\Models\AccountCodeSequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountGroupRepository extends BaseRepository
{
    /**
     * AccountGroupRepository constructor.
     *
     * @param AccountGroup $accountGroup
     */
    public function __construct(AccountGroup $accountGroup)
    {
        parent::__construct($accountGroup);
    }

    /**
     * Create a new account group.
     *
     * This method generates the next account code for the company,
     * sets the financial value (f_v) based on parent if provided,
     * creates the account group and its code sequence in a DB transaction.
     *
     * @param array $data
     * @return AccountGroup
     * @throws \Exception
     */
    public function create(array $data): AccountGroup
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? null;

            if (!$companyId) {
                throw new \Exception(__('messages.common.company_id_missing'));
            }

            // Set financial value based on parent group or default
            $fv = isset($data['parent_id']) 
                ? $this->model->where('id', $data['parent_id'])->value('f_v') 
                : null;
            $parentGroup = isset($data['parent_id']) 
                ? $this->model->where('id', $data['parent_id'])->first() 
                : false;
            if ($parentGroup && $parentGroup->is_party_group) {
                $data['is_party_group'] = true;
            }

            $data['f_v'] = $fv ?? 'f_v_1';

            // Generate next code
            $data['code'] = $this->nextCode($companyId);

            $accountGroup = $this->model->create($data);

            // Create account code sequence
            $accountGroup->accountCodeSequence()->create([
                'last_code' => $accountGroup->code * 100,
                'company_id' => $companyId,
                'created_by' => current_user_id(),
            ]);

            return $accountGroup;
        });
    }

    /**
     * Generate the next account code for a company.
     *
     * @param int $companyId
     * @return int
     */
    private function nextCode(int $companyId): int
    {
        $maxCode = $this->model->where('company_id', $companyId)->max('code');

        return ($maxCode ?? 0) + 10;
    }

    // /**
    //  * Restore a soft-deleted account group by UUID.
    //  *
    //  * This will also restore its parent chain and associated account code sequences.
    //  *
    //  * @param string $uuid
    //  * @return void
    //  * @throws \Exception
    //  */
    // public function restoreByUuid(string $uuid): void
    // {
    //     $group = $this->withTrashed()->where('uuid', $uuid)->first();

    //     if (!$group) {
    //         throw new \Exception(__('messages.account_group.not_found'));
    //     }

    //     $this->restoreParentChain($group);

    //     AccountCodeSequence::withTrashed()
    //         ->where('account_group_id', $group->id)
    //         ->restore();

    //     $group->deleted_by = null; 
    //     $group->save();
    //     $group->restore();
    // }

    /**
     * Check if the account group has child groups.
     *
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function hasChildren(AccountGroup $accountGroup): bool
    {
        return $accountGroup->children()->exists();
    }

    /**
     * Check if the account group has associated accounts.
     *
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function hasAccounts(AccountGroup $accountGroup): bool
    {
        return $accountGroup->accounts()->exists();
    }

    /**
     * Get all possible parent groups for a company.
     *
     * Optionally exclude a specific group by ID.
     *
     * @param int $companyId
     * @param int|null $excludeId
     * @return Collection
     */
    public function getParentGroups(int $companyId, ?int $excludeId = null): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', true)
            ->when($excludeId, fn($query, $excludeId) => $query->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a collection of account groups for field validation.
     *
     * Returns an associative array of 'id' => 'f_v' for the company.
     *
     * @param int $companyId
     * @return Collection
     */
    public function accountGroupsForFieldValidation(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->pluck('f_v','id');
    }

    // /**
    //  * Recursively restore parent chain of a group if it is soft-deleted.
    //  *
    //  * @param AccountGroup $group
    //  * @return void
    //  */
    // protected function restoreParentChain(AccountGroup $group): void
    // {
    //     if ($group->parent_id) {
    //         $parent = $this->withTrashed()->find($group->parent_id);
            
    //         if ($parent && $parent->trashed()) {
    //             $parent->deleted_by = null;
    //             $parent->save();
    //             $parent->restore();

    //             AccountCodeSequence::withTrashed()
    //                 ->where('account_group_id', $group->parent_id)
    //                 ->restore();
                
    //             $this->restoreParentChain($parent);
    //         }
    //     }
    // }

    public function getBankAccountGroupId($companyId) {
        return $this->model->query()->where('code', '130')->where('company_id', $companyId)->first()->id;
    }

    public function getPartyAccountGroupIds($companyId) {
        return $this->model->query()->whereIn('code', ['270', '170'])->where('company_id', $companyId)->pluck('id');
    }

    public function getCashAccountGroupId($companyId) {
        return $this->model->query()->where('code', '120')->where('company_id', $companyId)->first()->id;
    }
}
