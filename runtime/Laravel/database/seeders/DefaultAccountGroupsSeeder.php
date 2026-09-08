<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DefaultAccountGroupsSeeder extends Seeder
{
    public function run()
    {
        $groups = [

            ['name' => 'Assets', 'type' => 'asset', 'code' => 100, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Current Assets', 'type' => 'asset', 'code' => 110, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Cash-in-Hand', 'type' => 'asset', 'code' => 120, 'parent_code' => 100, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Bank Accounts', 'type' => 'asset', 'code' => 130, 'parent_code' => 100, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Stock-in-Hand', 'type' => 'asset', 'code' => 140, 'parent_code' => 100, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Loans & Advances (Assets)', 'type' => 'asset', 'code' => 150, 'parent_code' => 100, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Securities & Deposits (Assets)', 'type' => 'asset', 'code' => 160, 'parent_code' => 100, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Sundry Debtors', 'type' => 'asset', 'code' => 170, 'parent_code' => 100, 'f_v' => 'f_v_2', 'is_party_group' => true],

            ['name' => 'Investments', 'type' => 'asset', 'code' => 200, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Fixed Assets', 'type' => 'asset', 'code' => 210, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Liabilities', 'type' => 'liability', 'code' => 220, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Bank O/D Accounts', 'type' => 'liability', 'code' => 230, 'parent_code' => 220, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Current Liabilities', 'type' => 'liability', 'code' => 240, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Provisions / Expenses Payable', 'type' => 'liability', 'code' => 250, 'parent_code' => 240, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Duties & Taxes', 'type' => 'liability', 'code' => 260, 'parent_code' => 240, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Sundry Creditors', 'type' => 'liability', 'code' => 270, 'parent_code' => 240, 'f_v' => 'f_v_2', 'is_party_group' => true],


            ['name' => 'Loans(Liability)', 'type' => 'liability', 'code' => 310, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Secured Loans', 'type' => 'liability', 'code' => 320, 'parent_code' => 310, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Unsecured Loans', 'type' => 'liability', 'code' => 330, 'parent_code' => 310, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Capital Account', 'type' => 'liability', 'code' => 340, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Reserves & Surplus', 'type' => 'liability', 'code' => 350, 'parent_code' => 340, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Profit & Loss', 'type' => 'liability', 'code' => 360, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],

            ['name' => 'Pre-operative Expenses', 'type' => 'expense', 'code' => 370, 'parent_code' => null, 'f_v' => 'f_v_3', 'is_party_group' => false],

            ['name' => 'Revenue Accounts', 'type' => 'income', 'code' => 380, 'parent_code' => null, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Sales', 'type' => 'income', 'code' => 390, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Direct Income (Operational)', 'type' => 'income', 'code' => 400, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Indirect Income', 'type' => 'income', 'code' => 410, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Indirect / Administrative Expenses', 'type' => 'expense', 'code' => 420, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Direct / Manufacturing Expenses', 'type' => 'expense', 'code' => 440, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Purchase', 'type' => 'expense', 'code' => 450, 'parent_code' => 380, 'f_v' => 'f_v_3', 'is_party_group' => false],

            ['name' => 'Suspense Account', 'type' => 'liability', 'code' => 460, 'parent_code' => null, 'f_v' => 'f_v_1', 'is_party_group' => false],

        ];

        $groups = array_map(function ($group) {
            $group['slug'] = Str::slug($group['name']);
            return $group;
        }, $groups);

        DB::table('default_account_groups')->upsert(
            $groups,
            ['code'],
            ['name', 'type', 'parent_code', 'f_v', 'status', 'is_party_group']
        );
    }
}
