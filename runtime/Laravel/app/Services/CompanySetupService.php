<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountCodeSequence;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\PurchaseType;
use App\Repositories\CommonRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySetupService
{

    protected CommonRepository $commonRepo;
    protected UserRepository $userRepo;

    public function __construct(
        CommonRepository $commonRepo,
        UserRepository $userRepo,
    ) {
        $this->commonRepo = $commonRepo;
        $this->userRepo = $userRepo;
    }


    public function setupFor(Company $company, array $data): void
    {
        $financialYear = $this->createFinancialYear($company, $data);

        $this->assignDefaultUsers($company);

        $this->assignDefaultAccountGroup($company);
        $this->assignDefaultAccounts($company,  $financialYear->id);
        // $this->assignDefaultSaleTypes($company);
        // $this->assignDefaultPurchaseTypes($company);
        $this->assignDefaultUnits($company);
        $this->assignDefaultTaxCategories($company);
        $this->assignDefaultItemGroups($company);
        $this->assignDefaultBillSundries($company);

        
    }

    protected function createFinancialYear(Company $company, array $data): FinancialYear
    {
        $start = Carbon::createFromFormat('Y-m-d', $data['financial_year_start']);

        $end = $start->month < 4
            ? Carbon::create($start->year, 3, 31)
            : Carbon::create($start->year + 1, 3, 31);

        $financialYearName = 'FY ' . $start->year . '-' . substr($end->year, -2);

        return $company->financialYears()->create([
            ...Arr::only($data, ['uuid']),
            'name' => $financialYearName,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'is_current' => true,
        ]);
    }

    protected function assignDefaultUsers(Company $company): void
    {
        $users = $this->userRepo->query()->where('is_default', true)->pluck('id');

        $authId = Auth::id();
        $users = $users->push($authId)->unique();

        $company->users()->attach($users, [
            'status' => true,
            'assigned_by' => $authId,
            'assigned_at' => now(),
            'created_at' => now(),
        ]);
    }

    protected function assignDefaultAccountGroup(Company $company): void
    {

        $groups = DB::table('default_account_groups')
            ->where('status', true)

            ->get();

        foreach ($groups as $group) {
            $parentId = null;

            if ($group->parent_code !== null) {
                $parentId = AccountGroup::where('code', $group->parent_code)
                    ->where('company_id', $company->id)
                    ->where('status', true)
                    ->value('id');
            }

            $data = [
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'name' => $group->name,
                'code' => $group->code,
                'type' => $group->type,
                'parent_id' => $parentId,
                'f_v' => $group->f_v,
                'is_system' => true,
                'is_party_group' => $group->is_party_group,
                'status' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            $id = DB::table('account_groups')->insertGetId($data);

            $seqCode = [
                'account_group_id' => $id,
                'last_code' => $group->code * 100,
                'company_id' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('account_code_sequences')->insert($seqCode);
        }
    }

    protected function assignDefaultAccounts(Company $company, int $financialYearId): void
    {
        $accounts = DB::table('default_accounts')
            ->where('status', true)
            ->get();

        foreach ($accounts as $account) {
            $groupId = AccountGroup::where('code', $account->group_code)->where('company_id', $company->id)
                ->where('status', true)->value('id');

            $sequenceCode = AccountCodeSequence::where('account_group_id', $groupId)->first();
           
            $accountId = DB::table('accounts')->insertGetId([
                'uuid' => Str::uuid(),
                'account_group_id' => $groupId,
                'company_id' => $company->id,
                'name' => $account->name,
                'party_type' => $account->party_type,
                'print_name' => $account->name,
                'code' => $sequenceCode->last_code,
                'is_system' => true,
                'is_hidden' => $account->is_hidden,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            $account = Account::find($accountId);

            $account->preference()->create();
            $account->bankDetail()->create();
            $account->taxDetail()->create();
            $account->yearBalances()->create([
                'company_id' => $company->id,
                'financial_year_id' => $financialYearId,
            ]);

            $sequenceCode->increment('last_code');
        }
    }

    protected function assignDefaultPurchaseTypes(Company $company): void
    {
        $purchaseTypes = DB::table('default_purchase_types')
            ->where('status', true)
            ->get();
        $accountId = Account::where('company_id', $company->id)->where('code', 45000)->value('id');

        foreach ($purchaseTypes as $type) {
            DB::table('purchase_types')->insert([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'account_id' => $accountId,
                'name' => $type->name,
                'region' => $type->region,
                'taxation_type' => $type->taxation_type,
                'cgst' => $type->cgst,
                'sgst' => $type->sgst,
                'igst' => $type->igst,
                'is_system' => true,
                'transaction_type' => $type->transaction_type,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function assignDefaultSaleTypes(Company $company): void
    {
        $saleTypes = DB::table('default_sale_types')
            ->where('status', true)
            ->get();

        $accountId = Account::where('company_id', $company->id)->where('code', 39000)->value('id');

        foreach ($saleTypes as $type) {
            DB::table('sale_types')->insert([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'account_id' => $accountId,
                'name' => $type->name,
                'region' => $type->region,
                'taxation_type' => $type->taxation_type,
                'cgst' => $type->cgst,
                'sgst' => $type->sgst,
                'igst' => $type->igst,
                'is_system' => true,
                'transaction_type' => $type->transaction_type,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }


    protected function assignDefaultUnits(Company $company): void
    {
        $units = DB::table('default_units')
            ->where('status', true)
            ->get();

        foreach ($units as $unit) {
            DB::table('units')->insert([
                'uuid' => Str::uuid(),
                'name' => $unit->name,
                'print_name' => $unit->name,
                'company_id' => $company->id,
                'uqc' => $unit->uqc,
                'code' => $unit->code,
                'is_system' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function assignDefaultTaxCategories(Company $company): void
    {
        $taxCategories = DB::table('default_tax_categories')
            ->where('status', true)
            ->get();

        foreach ($taxCategories as $taxCategory) {
            DB::table('tax_categories')->insert([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'name' => $taxCategory->name,
                'type' => $taxCategory->type,
                'zero_tax_type' => $taxCategory->zero_tax_type,
                'code' => $taxCategory->code,
                'cgst' => $taxCategory->cgst,
                'sgst' => $taxCategory->sgst,
                'igst' => $taxCategory->igst,
                'is_system' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function  assignDefaultItemGroups(Company $company):void
    {
        $itemGroups = [1 => 'General'];

        foreach ($itemGroups as $itemGroup) {
            DB::table('item_groups')->insert([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'name' => $itemGroup,
                // 'is_system' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function  assignDefaultBillSundries(Company $company):void
    {
        $billSundries = DB::table('default_bill_sundries')->get();

        foreach ($billSundries as $billSundry) {
            $billSundry->purchase_account_id = null;
            $billSundry->purchase_party_account_id = null;

            $billSundry->sale_account_id = null;
            $billSundry->sale_party_account_id = null;

            if($billSundry->purchase_account_code){
                $billSundry->purchase_account_id = Account::where('company_id', $company->id)->where('code', $billSundry->purchase_account_code)->value('id');
            }

            if($billSundry->purchase_party_account_code){
                $billSundry->purchase_party_account_id = Account::where('company_id', $company->id)->where('code', $billSundry->purchase_party_account_code)->value('id');
            }

            if($billSundry->sale_account_code){
                $billSundry->sale_account_id = Account::where('company_id', $company->id)->where('code', $billSundry->sale_account_code)->value('id');
            }

            if($billSundry->sale_party_account_code){
                $billSundry->sale_party_account_id = Account::where('company_id', $company->id)->where('code', $billSundry->sale_party_account_code)->value('id');
            }
            DB::table('bill_sundries')->insert([
                'uuid'          => Str::uuid(),
                'company_id'    => $company->id,
                'name'          => $billSundry->name,
                'print_name'    => $billSundry->name,
                'bill_sundry_type' => $billSundry->bill_sundry_type,
                'is_system'     => true,
                'apply_on'      => $billSundry->apply_on,
                'default_value' => $billSundry->default_value,
                'bill_sundry_nature' => $billSundry->bill_sundry_nature,
                'bill_sundry_amount_round_off' => $billSundry->bill_sundry_amount_round_off,
                'purchase_adjust_in_amount' => $billSundry->purchase_adjust_in_amount,
                'purchase_account_type' => $billSundry->purchase_account_type,
                'purchase_account_id'   => $billSundry->purchase_account_id,
                'purchase_party_account_id' => $billSundry->purchase_party_account_id,
                'sale_adjust_in_amount' => $billSundry->sale_adjust_in_amount,
                'sale_account_type' => $billSundry->sale_account_type,
                'sale_account_id'   => $billSundry->sale_account_id,
                'sale_party_account_id' => $billSundry->sale_party_account_id,
                'calculation_type' => $billSundry->calculation_type,
                'purchase_adjust_in_party_amount' => $billSundry->purchase_adjust_in_party_amount,
                'sale_adjust_in_party_amount' => $billSundry->sale_adjust_in_party_amount,
                'purchase_party_account_type' => $billSundry->purchase_party_account_type,
                'sale_party_account_type' => $billSundry->sale_party_account_type,
                'purchase_post_over_and_above' => $billSundry->purchase_post_over_and_above,
                'sale_post_over_and_above' => $billSundry->sale_post_over_and_above,
                'preload_in_sales' => $billSundry->preload_in_sales,
                'sales_preload_order' => $billSundry->sales_preload_order,
                'preload_in_purchases' => $billSundry->preload_in_purchases,
                'purchases_preload_order' => $billSundry->purchases_preload_order,
                'code' => $billSundry->code,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),

            ]);
        }
    }
}
