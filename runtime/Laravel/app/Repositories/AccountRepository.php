<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\AccountCodeSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountRepository extends BaseRepository
{

    public function __construct(Account $account)
    {
        parent::__construct($account);
    }

    /**
     * Create a new account.
     */
    public function create(array $data): Account
    {

        // return DB::transaction(function () use ($data) {

            $companyId = $data['company_id'];
            //Get Code 
            $sequenceCode = AccountCodeSequence::where('account_group_id', $data['account_group_id'])->where('company_id',  $companyId)->first();
            
            if (!$sequenceCode) {
                throw new \Exception("Account code sequence not found for company [$companyId] and group [{$data['account_group_id']}]");
            }

            $account = $this->model->create(array_merge($data, [
                'code' => $sequenceCode->last_code,
                'gst_type' => $data['state_id'] == company_state_id() ? Account::GST_TYPE_LOCAL : Account::GST_TYPE_INTERSTATE,
                'cheque_master_id' => $data['cheque_id'] ?? null,
                'rtgs_form_id' => $data['rtgs_id'] ?? null,
                'created_by' => current_user_id(),
            ]));
        
            $account->preference()->create(Arr::only($data, ['transport_mode', 'sale_commission_rate', 'sale_unit_id', 'purchase_commission_rate', 'purchase_unit_id', 'sale_type_id', 'station','purchase_type_id', 'distance','contact_person','transport']));
            $account->bankDetail()->create(Arr::only($data, ['bank_name', 'bank_ifsc','bank_beneficiary_name','bank_branch_name','bank_account_number','rtgs_form_view_id']));
            $account->taxDetail()->create(Arr::only($data, ['gst_number', 'pan', 'tin', 'hsn_sac_code','type_of_dealer','filing_frequency','tax_category_id','hsn_sac_code','itc_eligibility','rcm_nature','pan','tin','tax_type','gst_type', 'tds_category_id', 'payee_category_id']));
            // $account->yearBalances()->create([
            //     ...Arr::only($data, ['opening_balance', 'opening_type']),
            //     'financial_year_id' => $data['financial_year_id'],
            //     'company_id' => $companyId,
            // ]);

            $sequenceCode->increment('last_code');
            
            return $account;
        // });
    }

    /**
     * Update an existing account.
     */
    // public function update(Account $account, array $data): Account
    // {
    //     return DB::transaction(function () use ($account, $data) {
    
            
    //         $account->update($data);
    
            
    //         if ($account->preference) {
    //             $account->preference->update(
    //                 Arr::only($data, [
    //                     'transport_mode', 'sale_type_id', 'station',
    //                     'purchase_type_id', 'distance', 'contact_person', 'transport'
    //                 ])
    //             );
    //         }
    
    //         if ($account->bankDetail) {
    //             $account->bankDetail->update(
    //                 Arr::only($data, [
    //                     'bank_name', 'bank_ifsc', 'bank_beneficiary_name',
    //                     'bank_branch_name', 'bank_account_number', 'rtgs_form_view_id'
    //                 ])
    //             );
    //         }
    
    //         if ($account->taxDetail) {
    //             $account->taxDetail->update(
    //                 Arr::only($data, [
    //                     'gst_number', 'pan', 'tin', 'hsn_sac_code',
    //                     'type_of_dealer', 'filing_frequency', 'tax_category_id',
    //                     'itc_eligibility', 'rcm_nature', 'tax_type', 'gst_type'
    //                 ])
    //             );
    //         }
    
    //         $yearBalance = $account->yearBalances()->where('financial_year_id', $data['financial_year_id'])->first();
    //         if ($yearBalance) {
    //             $yearBalance->update(
    //                 Arr::only($data, ['opening_balance', 'opening_type'])
    //             );
    //         }
    
    //         return $account;
    //     });
    // }
    public function update(Model $model, array $data): Model
    {
        /** @var Account $account */
        $account = $model; // cast to Account

        return DB::transaction(function () use ($account, $data) {
            $account->update(array_merge($data, [
                'gst_type' => $data['state_id'] == company_state_id() ? Account::GST_TYPE_LOCAL : Account::GST_TYPE_INTERSTATE,
                'cheque_master_id' => $data['cheque_id'] ?? null,
                'rtgs_form_id' => $data['rtgs_id'] ?? null,
                'updated_by' => current_user_id(),
            ]));

            if ($account->preference) {
                $account->preference->update(
                    Arr::only($data, [
                        'transport_mode', 'sale_type_id', 'station',
                        'sale_commission_rate',
                        'sale_unit_id',
                        'purchase_commission_rate',
                        'purchase_unit_id',
                        'purchase_type_id', 'distance', 'contact_person', 'transport'
                    ])
                );
            }

            if ($account->bankDetail) {
                $account->bankDetail->update(
                    Arr::only($data, [
                        'bank_name', 'bank_ifsc', 'bank_beneficiary_name',
                        'bank_branch_name', 'bank_account_number', 'rtgs_form_view_id'
                    ])
                );
            }

            if ($account->taxDetail) {
                $account->taxDetail->update(
                    Arr::only($data, [
                        'gst_number', 'pan', 'tin', 'hsn_sac_code',
                        'type_of_dealer', 'filing_frequency', 'tax_category_id',
                        'itc_eligibility', 'rcm_nature', 'tax_type', 'gst_type',
                        'tds_category_id', 'payee_category_id'
                    ])
                );
            }

            // $yearBalance = $account->yearBalances()
            //     ->where('financial_year_id', $data['financial_year_id'])
            //     ->first();

            // if ($yearBalance) {
            //     $yearBalance->update(
            //         Arr::only($data, ['opening_balance', 'opening_type'])
            //     );
            // }

            return $account->fresh(); // still returns Model, but actually Account
        });
    }
    

    /**
     * Soft delete an Account and all its related data.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        /** @var Account $account */
        $account = $model;

        return DB::transaction(function () use ($account) {
            // Delete related preference if exists
            if ($account->preference) {
                $account->preference->delete();
            }

            // Delete bank details if exists
            if ($account->bankDetail) {
                $account->bankDetail->delete();
            }

            // Delete tax details if exists
            if ($account->taxDetail) {
                $account->taxDetail->delete();
            }

            // Delete all year balances for this account
            // $account->yearBalances()->delete();

            // Finally delete the main account record
            return $account->delete();
        });
    }

    // /**
    //  * Restore soft-deleted by UUID (and its parent chain if needed).
    //  */
    // public function restoreByUuid(string $uuid, $financialYearId): void
    // {
    //     $account = $this->withTrashed()->where('uuid', $uuid)->first();
    
    //     if (!$account) {
    //         throw new \Exception(__('messages.account_group.not_found'));
    //     }
    
    //     DB::transaction(function () use ($account, $financialYearId) {
    //         // Restore main account
    //         $account->deleted_by = null;
    //         $account->save();
    //         $account->restore();
    
    //         // Restore related models manually
    //         if ($account->preference()->withTrashed()->exists()) {
    //             $account->preference()->withTrashed()->restore();
    //         }
    
    //         if ($account->bankDetail()->withTrashed()->exists()) {
    //             $account->bankDetail()->withTrashed()->restore();
    //         }
    
    //         if ($account->taxDetail()->withTrashed()->exists()) {
    //             $account->taxDetail()->withTrashed()->restore();
    //         }
    
    //         // Restore all year balances
    //         $account->yearBalances()->withTrashed()->where('financial_year_id',$financialYearId)->restore();
    //     });
    // }

    /**
     * Check if account has children.
     */
    public function hasChildren(Account $account): bool
    {
        return $account->children()->exists();
    }

    /**
     * Get all possible parent groups for a company.
     */
    public function getParentGroups(int $companyId, ?int $excludeId = null): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', true)
            ->when($excludeId, fn($query, $excludeId) => $query->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get();
    }

    
    public function getSupplierAndVendorId() {
            
    }


    // /**
    //  * Restore parent chain recursively.
    //  */
    // protected function restoreParentChain(Account $group): void
    // {
    //     if ($group->parent_id) {
    //         $parent = $this->withTrashed()->find($group->parent_id);
    //         if ($parent && $parent->trashed()) {
    //             $parent->deleted_by = null;
    //             $parent->save();
    //             $parent->restore();
    //             $this->restoreParentChain($parent);
    //         }
    //     }
    // }

    public function getAccountByCode($companyId,$code){
        if(!$code){
            return null;
        };

        $account = $this->model->query()->where('company_id', $companyId)->where('code', $code)->first();

        return $account ?? null;
    }

    public function getAccountIds(int $companyId,?array $groups): array {
        return $this->model->query()->where('company_id', $companyId)
        ->where('status', true)
        ->where('is_hidden', false)
        ->when($groups, fn($query, $groups) => $query->whereIn('party_type', $groups))
        ->pluck('id')->toArray();
    }
}
