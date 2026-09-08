<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountBankDetail;
use App\Models\AccountGroup;
use App\Models\AccountPreference;
use App\Models\AccountTaxDetail;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Condition;
use App\Models\Destination;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\PayeeCategory;
use App\Models\PurchaseType;
use App\Models\SaleType;
use App\Models\TaxCategory;
use App\Models\TdsCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultCompanySeeder extends Seeder
{
    // public function run(): void
    // {

    //     // when need code add here
    // }

      public function run(): void
    {
        // ─── COMPANY & FINANCIAL YEAR ────────────────────────────────────────────────

        $company = Company::firstOrCreate(
            ['name' => 'Mahakali Corporation'],
            [
                'uuid'                 => Str::uuid(),
                'print_name'           => 'Mahakali Corporation',
                'legal_name'           => 'Mahakali Corporation',
                'financial_year_start' => '2025-04-01',
                'type_of_dealer'       => 'registered',
                'status'               => true,
                'code'                 => 'MC',
                'country_id'           => 1,
                'state_id'             => 1,
            ]
        );

        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'start_date' => '2026-04-01'],
            [
                'uuid'       => Str::uuid(),
                'name'       => '2026-2027',
                'end_date'   => '2027-03-31',
                'is_current' => true,
                'status'     => true,
            ]
        );

        $users = User::where('is_default', true)->get();
        foreach ($users as $user) {
            CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id, 'is_active' => true],
                ['status' => true]
            );
        }

        $cid       = $company->id;
        $adminUser = $users->first()?->id ?? 1;

        // ─── ACCOUNT GROUPS ──────────────────────────────────────────────────────────

        $groupDefs = [
            // Root groups (no parent)
            ['name' => 'Assets',                             'type' => 'asset',     'code' => '100', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Current Assets',                     'type' => 'asset',     'code' => '110', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Investments',                        'type' => 'asset',     'code' => '200', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Fixed Assets',                       'type' => 'asset',     'code' => '210', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Liabilities',                        'type' => 'liability', 'code' => '220', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Current Liabilities',                'type' => 'liability', 'code' => '240', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Loans(Liability)',                   'type' => 'liability', 'code' => '310', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Capital Account',                    'type' => 'liability', 'code' => '340', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Profit & Loss',                      'type' => 'liability', 'code' => '360', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Pre-operative Expenses',             'type' => 'expense',   'code' => '370', 'parent_code' => null,  'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Revenue Accounts',                   'type' => 'income',    'code' => '380', 'parent_code' => null,  'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Suspense Account',                   'type' => 'liability', 'code' => '460', 'parent_code' => null,  'f_v' => 'f_v_1', 'is_party_group' => false],
            // Child groups
            ['name' => 'Cash-in-Hand',                       'type' => 'asset',     'code' => '120', 'parent_code' => '100', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Bank Accounts',                      'type' => 'asset',     'code' => '130', 'parent_code' => '100', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Stock-in-Hand',                      'type' => 'asset',     'code' => '140', 'parent_code' => '100', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Loans & Advances (Assets)',          'type' => 'asset',     'code' => '150', 'parent_code' => '100', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Securities & Deposits (Assets)',     'type' => 'asset',     'code' => '160', 'parent_code' => '100', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Sundry Debtors',                     'type' => 'asset',     'code' => '170', 'parent_code' => '100', 'f_v' => 'f_v_2', 'is_party_group' => true],
            ['name' => 'Bank O/D Accounts',                  'type' => 'liability', 'code' => '230', 'parent_code' => '220', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Provisions / Expenses Payable',      'type' => 'liability', 'code' => '250', 'parent_code' => '240', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Duties & Taxes',                     'type' => 'liability', 'code' => '260', 'parent_code' => '240', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Sundry Creditors',                   'type' => 'liability', 'code' => '270', 'parent_code' => '240', 'f_v' => 'f_v_2', 'is_party_group' => true],
            ['name' => 'Secured Loans',                      'type' => 'liability', 'code' => '320', 'parent_code' => '310', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Unsecured Loans',                    'type' => 'liability', 'code' => '330', 'parent_code' => '310', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Reserves & Surplus',                 'type' => 'liability', 'code' => '350', 'parent_code' => '340', 'f_v' => 'f_v_1', 'is_party_group' => false],
            ['name' => 'Sales',                              'type' => 'income',    'code' => '390', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Direct Income (Operational)',        'type' => 'income',    'code' => '400', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Indirect Income',                    'type' => 'income',    'code' => '410', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Indirect / Administrative Expenses', 'type' => 'expense',   'code' => '420', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Direct / Manufacturing Expenses',    'type' => 'expense',   'code' => '440', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
            ['name' => 'Purchase',                           'type' => 'expense',   'code' => '450', 'parent_code' => '380', 'f_v' => 'f_v_3', 'is_party_group' => false],
        ];

        $groupMap = [];

        // First pass: root groups (parent_code = null)
        foreach ($groupDefs as $g) {
            if ($g['parent_code'] !== null) continue;
            $group = AccountGroup::firstOrCreate(
                ['company_id' => $cid, 'code' => $g['code']],
                [
                    'uuid'           => Str::uuid(),
                    'name'           => $g['name'],
                    'type'           => $g['type'],
                    'f_v'            => $g['f_v'],
                    'parent_id'      => null,
                    'is_party_group' => $g['is_party_group'],
                    'is_system'      => true,
                    'status'         => true,
                    'created_by'     => $adminUser,
                ]
            );
            $groupMap[$g['code']] = $group->id;
        }

        // Second pass: child groups
        foreach ($groupDefs as $g) {
            if ($g['parent_code'] === null) continue;
            $group = AccountGroup::firstOrCreate(
                ['company_id' => $cid, 'code' => $g['code']],
                [
                    'uuid'           => Str::uuid(),
                    'name'           => $g['name'],
                    'type'           => $g['type'],
                    'f_v'            => $g['f_v'],
                    'parent_id'      => $groupMap[$g['parent_code']] ?? null,
                    'is_party_group' => $g['is_party_group'],
                    'is_system'      => true,
                    'status'         => true,
                    'created_by'     => $adminUser,
                ]
            );
            $groupMap[$g['code']] = $group->id;
        }

        $grp = fn(string $code) => $groupMap[$code] ?? null;

        // ─── SYSTEM ACCOUNTS ─────────────────────────────────────────────────────────
        // Code scheme: group_code * 100 + sequence (matches default_bill_sundries account_code references)

        $accountDefs = [
            // Cash & Bank
            ['name' => 'Cash',                        'party_type' => 'account', 'gst_type' => 'local',       'group' => '120', 'code' => 12000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Petty Cash',                  'party_type' => 'account', 'gst_type' => 'local',       'group' => '120', 'code' => 12001, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'SBI Bank A/C',                'party_type' => 'account', 'gst_type' => 'local',       'group' => '130', 'code' => 13000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'HDFC Bank A/C',               'party_type' => 'account', 'gst_type' => 'local',       'group' => '130', 'code' => 13001, 'is_hidden' => false, 'is_system' => true],
            // Stock
            ['name' => 'Stock in Hand',               'party_type' => 'account', 'gst_type' => 'local',       'group' => '140', 'code' => 14000, 'is_hidden' => false, 'is_system' => true],
            // Reserves
            ['name' => 'Opening Balance Difference',  'party_type' => 'account', 'gst_type' => 'local',       'group' => '350', 'code' => 35000, 'is_hidden' => true,  'is_system' => true],
            // Capital
            ['name' => 'Capital Account',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '340', 'code' => 34000, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Drawings',                    'party_type' => 'account', 'gst_type' => 'local',       'group' => '340', 'code' => 34001, 'is_hidden' => false, 'is_system' => false],
            // Sales accounts
            ['name' => 'Local Sales',                 'party_type' => 'account', 'gst_type' => 'local',       'group' => '390', 'code' => 39000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Export Sales',                'party_type' => 'account', 'gst_type' => 'interstate',  'group' => '390', 'code' => 39001, 'is_hidden' => false, 'is_system' => true],
            // Purchase accounts
            ['name' => 'Local Purchase',              'party_type' => 'account', 'gst_type' => 'local',       'group' => '450', 'code' => 45000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Import Purchase',             'party_type' => 'account', 'gst_type' => 'interstate',  'group' => '450', 'code' => 45001, 'is_hidden' => false, 'is_system' => true],
            // GST Duties & Taxes
            ['name' => 'CGST Input',                  'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'CGST Output',                 'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26001, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'SGST Input',                  'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26002, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'SGST Output',                 'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26003, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'IGST Input',                  'party_type' => 'account', 'gst_type' => 'interstate',  'group' => '260', 'code' => 26004, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'IGST Output',                 'party_type' => 'account', 'gst_type' => 'interstate',  'group' => '260', 'code' => 26005, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'GST RCM Payable',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26006, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'GST RCM Receivable',          'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26007, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'TDS Payable',                 'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26008, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'TDS on Purchase of Goods',    'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26009, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'TCS Payable',                 'party_type' => 'account', 'gst_type' => 'local',       'group' => '260', 'code' => 26010, 'is_hidden' => false, 'is_system' => true],
            // Indirect Income
            ['name' => 'Cash Discount Received',      'party_type' => 'account', 'gst_type' => 'local',       'group' => '410', 'code' => 41000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Penalty Received',            'party_type' => 'account', 'gst_type' => 'local',       'group' => '410', 'code' => 41001, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Rebate Received',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '410', 'code' => 41002, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Commission Received',         'party_type' => 'account', 'gst_type' => 'local',       'group' => '410', 'code' => 41003, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Interest Received',           'party_type' => 'account', 'gst_type' => 'local',       'group' => '410', 'code' => 41004, 'is_hidden' => false, 'is_system' => false],
            // Indirect / Admin Expenses
            ['name' => 'Cash Discount Allowed',       'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Round Off',                   'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42001, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Rent Expenses',               'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42002, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Salary Expenses',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42003, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Electricity Expenses',        'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42004, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Telephone Expenses',          'party_type' => 'account', 'gst_type' => 'local',       'group' => '420', 'code' => 42005, 'is_hidden' => false, 'is_system' => false],
            // Direct / Manufacturing Expenses
            ['name' => 'Freight Charges',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44000, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Transport Expenses',          'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44001, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Packing Charges',             'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44002, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Labour Charges',              'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44003, 'is_hidden' => false, 'is_system' => true],
            ['name' => 'Factory Expenses',            'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44004, 'is_hidden' => false, 'is_system' => false],
            ['name' => 'Power & Fuel',                'party_type' => 'account', 'gst_type' => 'local',       'group' => '440', 'code' => 44005, 'is_hidden' => false, 'is_system' => false],
            // Broker
            // ['name' => 'Self',                        'party_type' => 'broker',  'gst_type' => 'local',       'group' => '270', 'code' => 27000, 'is_hidden' => false, 'is_system' => true],
        ];

        $accountMap = [];
        foreach ($accountDefs as $a) {
            $acct = Account::firstOrCreate(
                ['company_id' => $cid, 'code' => $a['code']],
                [
                    'uuid'             => Str::uuid(),
                    'name'             => $a['name'],
                    'print_name'       => $a['name'],
                    'account_group_id' => $grp($a['group']),
                    'party_type'       => $a['party_type'],
                    'gst_type'         => $a['gst_type'],
                    'is_hidden'        => $a['is_hidden'],
                    'is_system'        => $a['is_system'],
                    'is_billwise'      => false,
                    'country_id'       => 1,
                    'status'           => true,
                    'created_by'       => $adminUser,
                ]
            );

            AccountPreference::firstOrCreate(['account_id' => $acct->id]);
            AccountBankDetail::firstOrCreate(['account_id' => $acct->id]);
            AccountTaxDetail::firstOrCreate(['account_id' => $acct->id]);

            $accountMap[$a['code']] = $acct->id;
        }

        // Backfill related records for ALL accounts missing them across all companies
        Account::each(function (Account $account) {
            AccountPreference::firstOrCreate(['account_id' => $account->id]);
            AccountBankDetail::firstOrCreate(['account_id' => $account->id]);
            AccountTaxDetail::firstOrCreate(['account_id' => $account->id]);
        });

        $acc = fn(int $code) => $accountMap[$code] ?? null;

        // ─── UNITS ───────────────────────────────────────────────────────────────────

        $unitDefs = [
            ['name' => 'Kilogram',   'print_name' => 'Kg',   'uqc' => 'KGS', 'code' => 'KGS'],
            ['name' => 'Gram',       'print_name' => 'Gm',   'uqc' => 'GMS', 'code' => 'GMS'],
            ['name' => 'Liter',      'print_name' => 'Ltr',  'uqc' => 'LTR', 'code' => 'LTR'],
            ['name' => 'Milliliter', 'print_name' => 'Ml',   'uqc' => 'MLT', 'code' => 'MLT'],
            ['name' => 'Piece',      'print_name' => 'Pcs',  'uqc' => 'PCS', 'code' => 'PCS'],
            ['name' => 'Meter',      'print_name' => 'Mtr',  'uqc' => 'MTR', 'code' => 'MTR'],
            ['name' => 'Centimeter', 'print_name' => 'Cm',   'uqc' => 'CMT', 'code' => 'CMT'],
            ['name' => 'Nos',        'print_name' => 'Nos',  'uqc' => 'NOS', 'code' => 'NOS'],
            ['name' => 'Ton',        'print_name' => 'Ton',  'uqc' => 'TON', 'code' => 'TON'],
            ['name' => 'Box',        'print_name' => 'Box',  'uqc' => 'BOX', 'code' => 'BOX'],
            ['name' => 'Bag',        'print_name' => 'Bag',  'uqc' => 'BAG', 'code' => 'BAG'],
            ['name' => 'Quintal',    'print_name' => 'Qtl',  'uqc' => 'QTL', 'code' => 'QTL'],
        ];

        $unitMap = [];
        foreach ($unitDefs as $u) {
            $unit = Unit::firstOrCreate(
                ['company_id' => $cid, 'code' => $u['code']],
                [
                    'uuid'       => Str::uuid(),
                    'name'       => $u['name'],
                    'print_name' => $u['print_name'],
                    'uqc'        => $u['uqc'],
                    'is_system'  => true,
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
            $unitMap[$u['name']] = $unit->id;
        }

        // ─── TAX CATEGORIES ──────────────────────────────────────────────────────────

        $taxDefs = [
            ['name' => 'Goods Exempt',        'type' => 'goods',    'zero_tax_type' => 'exempt',     'code' => 1000, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Goods Nil Rated',     'type' => 'goods',    'zero_tax_type' => 'nil_rated',  'code' => 1001, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Goods Zero Rated',    'type' => 'goods',    'zero_tax_type' => 'zero_rated', 'code' => 1002, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Goods Non-GST',       'type' => 'goods',    'zero_tax_type' => 'non_gst',    'code' => 1003, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Goods 5%',            'type' => 'goods',    'zero_tax_type' => null,         'code' => 1004, 'cgst' => 2.50,  'sgst' => 2.50,  'igst' => 5.00],
            ['name' => 'Goods 12%',           'type' => 'goods',    'zero_tax_type' => null,         'code' => 1005, 'cgst' => 6.00,  'sgst' => 6.00,  'igst' => 12.00],
            ['name' => 'Goods 18%',           'type' => 'goods',    'zero_tax_type' => null,         'code' => 1006, 'cgst' => 9.00,  'sgst' => 9.00,  'igst' => 18.00],
            ['name' => 'Goods 28%',           'type' => 'goods',    'zero_tax_type' => null,         'code' => 1007, 'cgst' => 14.00, 'sgst' => 14.00, 'igst' => 28.00],
            ['name' => 'Services Exempt',     'type' => 'services', 'zero_tax_type' => 'exempt',     'code' => 2000, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Services Nil Rated',  'type' => 'services', 'zero_tax_type' => 'nil_rated',  'code' => 2001, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Services Zero Rated', 'type' => 'services', 'zero_tax_type' => 'zero_rated', 'code' => 2002, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Services Non-GST',    'type' => 'services', 'zero_tax_type' => 'non_gst',    'code' => 2003, 'cgst' => 0,     'sgst' => 0,     'igst' => 0],
            ['name' => 'Services 5%',         'type' => 'services', 'zero_tax_type' => null,         'code' => 2004, 'cgst' => 2.50,  'sgst' => 2.50,  'igst' => 5.00],
            ['name' => 'Services 12%',        'type' => 'services', 'zero_tax_type' => null,         'code' => 2005, 'cgst' => 6.00,  'sgst' => 6.00,  'igst' => 12.00],
            ['name' => 'Services 18%',        'type' => 'services', 'zero_tax_type' => null,         'code' => 2006, 'cgst' => 9.00,  'sgst' => 9.00,  'igst' => 18.00],
            ['name' => 'Services 28%',        'type' => 'services', 'zero_tax_type' => null,         'code' => 2007, 'cgst' => 14.00, 'sgst' => 14.00, 'igst' => 28.00],
        ];

        $taxMap = [];
        foreach ($taxDefs as $t) {
            $tax = TaxCategory::firstOrCreate(
                ['company_id' => $cid, 'code' => $t['code']],
                [
                    'uuid'          => Str::uuid(),
                    'name'          => $t['name'],
                    'type'          => $t['type'],
                    'zero_tax_type' => $t['zero_tax_type'],
                    'cgst'          => $t['cgst'],
                    'sgst'          => $t['sgst'],
                    'igst'          => $t['igst'],
                    'is_system'     => true,
                    'status'        => true,
                    'created_by'    => $adminUser,
                ]
            );
            $taxMap[$t['code']] = $tax->id;
        }

        // ─── SALE TYPES ──────────────────────────────────────────────────────────────
        // account_id → the sales ledger account for this sale type

        $saleTypeDefs = [
            ['name' => 'Local Nil Rated',     'region' => 'local',      'taxation_type' => 'nil_rated',  'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39000],
            ['name' => 'Central Nil Rated',   'region' => 'interstate', 'taxation_type' => 'nil_rated',  'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39001],
            ['name' => 'Local Exempted',      'region' => 'local',      'taxation_type' => 'exempt',     'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39000],
            ['name' => 'Central Exempted',    'region' => 'interstate', 'taxation_type' => 'exempt',     'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39001],
            ['name' => 'Local Non-GST',       'region' => 'local',      'taxation_type' => 'non_gst',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39000],
            ['name' => 'Central Non-GST',     'region' => 'interstate', 'taxation_type' => 'non_gst',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39001],
            ['name' => 'Local Zero Rated',    'region' => 'local',      'taxation_type' => 'zero_rated', 'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39000],
            ['name' => 'Central Zero Rated',  'region' => 'interstate', 'taxation_type' => 'zero_rated', 'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39001],
            ['name' => 'Local - 5%',          'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 2.50,  'sgst' => 2.50,  'igst' => 0,     'acct' => 39000],
            ['name' => 'Central - 5%',        'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 5.00,  'acct' => 39001],
            ['name' => 'Local - 12%',         'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 6.00,  'sgst' => 6.00,  'igst' => 0,     'acct' => 39000],
            ['name' => 'Central - 12%',       'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 12.00, 'acct' => 39001],
            ['name' => 'Local - 18%',         'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 9.00,  'sgst' => 9.00,  'igst' => 0,     'acct' => 39000],
            ['name' => 'Central - 18%',       'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 18.00, 'acct' => 39001],
            ['name' => 'Local - 28%',         'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 14.00, 'sgst' => 14.00, 'igst' => 0,     'acct' => 39000],
            ['name' => 'Central - 28%',       'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 28.00, 'acct' => 39001],
            ['name' => 'Export - Zero Rated', 'region' => 'interstate', 'taxation_type' => 'zero_rated', 'transaction_type' => 'export',   'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 39001],
        ];

        $saleTypeMap = [];
        foreach ($saleTypeDefs as $s) {
            $st = SaleType::firstOrCreate(
                ['company_id' => $cid, 'name' => $s['name']],
                [
                    'uuid'             => Str::uuid(),
                    'region'           => $s['region'],
                    'taxation_type'    => $s['taxation_type'],
                    'transaction_type' => $s['transaction_type'],
                    'cgst'             => $s['cgst'],
                    'sgst'             => $s['sgst'],
                    'igst'             => $s['igst'],
                    'account_id'       => $acc($s['acct']),
                    'is_system'        => true,
                    'status'           => true,
                    'created_by'       => $adminUser,
                ]
            );
            $saleTypeMap[$s['name']] = $st->id;
        }

        // ─── PURCHASE TYPES ──────────────────────────────────────────────────────────

        $purchaseTypeDefs = [
            ['name' => 'Local Nil Rated',    'region' => 'local',      'taxation_type' => 'nil_rated',  'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45000],
            ['name' => 'Central Nil Rated',  'region' => 'interstate', 'taxation_type' => 'nil_rated',  'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45001],
            ['name' => 'Local Exempted',     'region' => 'local',      'taxation_type' => 'exempt',     'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45000],
            ['name' => 'Central Exempted',   'region' => 'interstate', 'taxation_type' => 'exempt',     'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45001],
            ['name' => 'Local Non-GST',      'region' => 'local',      'taxation_type' => 'non_gst',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45000],
            ['name' => 'Central Non-GST',    'region' => 'interstate', 'taxation_type' => 'non_gst',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45001],
            ['name' => 'Local Zero Rated',   'region' => 'local',      'taxation_type' => 'zero_rated', 'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45000],
            ['name' => 'Central Zero Rated', 'region' => 'interstate', 'taxation_type' => 'zero_rated', 'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45001],
            ['name' => 'Local - 5%',         'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 2.50,  'sgst' => 2.50,  'igst' => 0,     'acct' => 45000],
            ['name' => 'Central - 5%',       'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 5.00,  'acct' => 45001],
            ['name' => 'Local - 12%',        'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 6.00,  'sgst' => 6.00,  'igst' => 0,     'acct' => 45000],
            ['name' => 'Central - 12%',      'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 12.00, 'acct' => 45001],
            ['name' => 'Local - 18%',        'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 9.00,  'sgst' => 9.00,  'igst' => 0,     'acct' => 45000],
            ['name' => 'Central - 18%',      'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 18.00, 'acct' => 45001],
            ['name' => 'Local - 28%',        'region' => 'local',      'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 14.00, 'sgst' => 14.00, 'igst' => 0,     'acct' => 45000],
            ['name' => 'Central - 28%',      'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'domestic', 'cgst' => 0,     'sgst' => 0,     'igst' => 28.00, 'acct' => 45001],
            ['name' => 'Import',             'region' => 'interstate', 'taxation_type' => 'taxable',    'transaction_type' => 'import',   'cgst' => 0,     'sgst' => 0,     'igst' => 0,     'acct' => 45001],
        ];

        $purchaseTypeMap = [];
        foreach ($purchaseTypeDefs as $p) {
            $pt = PurchaseType::firstOrCreate(
                ['company_id' => $cid, 'name' => $p['name']],
                [
                    'uuid'             => Str::uuid(),
                    'region'           => $p['region'],
                    'taxation_type'    => $p['taxation_type'],
                    'transaction_type' => $p['transaction_type'],
                    'cgst'             => $p['cgst'],
                    'sgst'             => $p['sgst'],
                    'igst'             => $p['igst'],
                    'account_id'       => $acc($p['acct']),
                    'is_system'        => true,
                    'status'           => true,
                    'created_by'       => $adminUser,
                ]
            );
            $purchaseTypeMap[$p['name']] = $pt->id;
        }

        // ─── PAYEE CATEGORIES ────────────────────────────────────────────────────────

        $payeeDefs = [
            'Individual - Residents', 'Individual - Non Residents', 'Domestic Company',
            'Foreign Company', 'Hindu Undivided Family', 'Partnership Firm',
            'Association of Persons', 'Body of Individuals', 'Co-operative Society', 'Trust',
        ];

        foreach ($payeeDefs as $name) {
            PayeeCategory::firstOrCreate(
                ['company_id' => $cid, 'payee_category' => $name],
                [
                    'uuid'       => Str::uuid(),
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
        }

        // ─── TDS CATEGORIES ──────────────────────────────────────────────────────────
        // Note: tds_categories.type enum is lowercase: tds / tcs / higher

        $tdsDefs = [
            ['section' => '192',   'category_name' => 'Salary',                    'description' => 'TDS on Salary',                         'rate' => null,  'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '192A',  'category_name' => 'EPF Withdrawal',             'description' => 'Premature withdrawal from EPF',          'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '193',   'category_name' => 'Interest on Securities',     'description' => 'Interest on securities',                 'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194',   'category_name' => 'Dividend',                   'description' => 'Dividend income',                        'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194A',  'category_name' => 'Bank/Post Office Interest',  'description' => 'Interest other than securities',          'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '194B',  'category_name' => 'Lottery Winnings',           'description' => 'Winning from lottery or puzzle',          'rate' => 30.00, 'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '194BB', 'category_name' => 'Horse Race Winnings',        'description' => 'Winning from horse races',                'rate' => 30.00, 'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '194C',  'category_name' => 'Contractor/Sub-contractor',  'description' => 'Payment to contractors',                  'rate' => 1.00,  'type' => 'tds',    'applicable_to' => 'Company'],
            ['section' => '194D',  'category_name' => 'Insurance Commission',       'description' => 'Commission on insurance',                 'rate' => 5.00,  'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194DA', 'category_name' => 'Life Insurance Maturity',    'description' => 'Payout on life insurance policies',       'rate' => 5.00,  'type' => 'tds',    'applicable_to' => 'Individual'],
            ['section' => '194H',  'category_name' => 'Commission or Brokerage',    'description' => 'Brokerage and commission payments',        'rate' => 5.00,  'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194I',  'category_name' => 'Rent (Land/Building)',       'description' => 'Rent for land or building',               'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Company'],
            ['section' => '194IA', 'category_name' => 'Property Sale >= 50L',       'description' => 'Transfer of immovable property',          'rate' => 1.00,  'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194J',  'category_name' => 'Professional Services',      'description' => 'Fees for professional services',          'rate' => 10.00, 'type' => 'tds',    'applicable_to' => 'Both'],
            ['section' => '194Q',  'category_name' => 'Purchase of Goods',          'description' => 'TDS on purchase > 50L',                   'rate' => 0.10,  'type' => 'tds',    'applicable_to' => 'Company'],
            ['section' => '206AB', 'category_name' => 'Higher TDS for Non-Filers',  'description' => 'TDS for non-filing taxpayers',            'rate' => 5.00,  'type' => 'higher', 'applicable_to' => 'Both'],
            ['section' => '206C',  'category_name' => 'TCS (Various)',              'description' => 'Tax collected at source',                 'rate' => null,  'type' => 'tcs',    'applicable_to' => 'Both'],
        ];

        foreach ($tdsDefs as $t) {
            TdsCategory::firstOrCreate(
                ['company_id' => $cid, 'section' => $t['section']],
                [
                    'uuid'          => Str::uuid(),
                    'category_name' => $t['category_name'],
                    'description'   => $t['description'],
                    'rate'          => $t['rate'],
                    'type'          => $t['type'],
                    'applicable_to' => $t['applicable_to'],
                    'status'        => true,
                    'created_by'    => $adminUser,
                ]
            );
        }

        // ─── BILL SUNDRIES ───────────────────────────────────────────────────────────
        // Use DB::table to bypass fillable restrictions on preload_* and is_system fields

        $billSundryDefs = [
            [
                'name' => 'CD', 'print_name' => 'CD', 'code' => '1001',
                'bill_sundry_type' => 'subtractive', 'calculation_type' => 'percentage',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(41000),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => true, 'sale_account_type' => null,
                'sale_account_id' => null, 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 1,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'CGST', 'print_name' => 'CGST', 'code' => '1002',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'percentage',
                'bill_sundry_nature' => 'gst', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(26000),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(26001), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => true, 'sales_preload_order' => 2,
                'preload_in_purchases' => true, 'purchases_preload_order' => 2,
                'is_system' => true, 'is_read_only' => true,
            ],
            [
                'name' => 'SGST', 'print_name' => 'SGST', 'code' => '1003',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'percentage',
                'bill_sundry_nature' => 'gst', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(26002),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(26003), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => true, 'sales_preload_order' => 1,
                'preload_in_purchases' => true, 'purchases_preload_order' => 3,
                'is_system' => true, 'is_read_only' => true,
            ],
            [
                'name' => 'IGST', 'print_name' => 'IGST', 'code' => '1004',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'percentage',
                'bill_sundry_nature' => 'gst', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(26004),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(26005), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => true, 'sales_preload_order' => 3,
                'preload_in_purchases' => true, 'purchases_preload_order' => 4,
                'is_system' => true, 'is_read_only' => true,
            ],
            [
                'name' => 'Freight', 'print_name' => 'Freight', 'code' => '1005',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(44000),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(44000), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => true, 'sales_preload_order' => 4,
                'preload_in_purchases' => true, 'purchases_preload_order' => 5,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'Labour', 'print_name' => 'Labour', 'code' => '1006',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(44003),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(44003), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => true, 'sales_preload_order' => 5,
                'preload_in_purchases' => true, 'purchases_preload_order' => 6,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'Penalty', 'print_name' => 'Penalty', 'code' => '1007',
                'bill_sundry_type' => 'subtractive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(41001),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => true, 'sale_account_type' => null,
                'sale_account_id' => null, 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 7,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'Rebate', 'print_name' => 'Rebate', 'code' => '1008',
                'bill_sundry_type' => 'subtractive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(41002),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => true, 'sale_account_type' => null,
                'sale_account_id' => null, 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 8,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'TDS(Purchase of Goods)', 'print_name' => 'TDS(Purchase of Goods)', 'code' => '1009',
                'bill_sundry_type' => 'subtractive', 'calculation_type' => 'percentage',
                'bill_sundry_nature' => 'tds', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(26009),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => true,
                'sale_adjust_in_amount' => true, 'sale_account_type' => null,
                'sale_account_id' => null, 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 9,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'Round Off(+)', 'print_name' => 'Round Off(+)', 'code' => '1010',
                'bill_sundry_type' => 'additive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => true, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(42001),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(42001), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 10,
                'is_system' => true, 'is_read_only' => false,
            ],
            [
                'name' => 'Round Off(-)', 'print_name' => 'Round Off(-)', 'code' => '1011',
                'bill_sundry_type' => 'subtractive', 'calculation_type' => 'fixed',
                'bill_sundry_nature' => 'other', 'apply_on' => 'basic',
                'bill_sundry_amount_round_off' => false, 'default_value' => 0,
                'purchase_adjust_in_amount' => false, 'purchase_account_type' => 'specify_account',
                'purchase_account_id' => $acc(42001),
                'purchase_adjust_in_party_amount' => true, 'purchase_party_account_type' => null,
                'purchase_party_account_id' => null, 'purchase_post_over_and_above' => false,
                'sale_adjust_in_amount' => false, 'sale_account_type' => 'specify_account',
                'sale_account_id' => $acc(42001), 'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null, 'sale_party_account_id' => null,
                'sale_post_over_and_above' => false,
                'preload_in_sales' => false, 'sales_preload_order' => 0,
                'preload_in_purchases' => true, 'purchases_preload_order' => 11,
                'is_system' => true, 'is_read_only' => false,
            ],
        ];

        foreach ($billSundryDefs as $b) {
            $exists = DB::table('bill_sundries')
                ->where('company_id', $cid)
                ->where('code', $b['code'])
                ->whereNull('deleted_at')
                ->exists();

            if (! $exists) {
                DB::table('bill_sundries')->insert(array_merge($b, [
                    'uuid'       => Str::uuid(),
                    'company_id' => $cid,
                    'status'     => true,
                    'is_active'  => true,
                    'created_by' => $adminUser,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // ─── DUMMY CUSTOMERS ─────────────────────────────────────────────────────────

        $customers = [
            ['name' => 'Agarwal Traders',        'city' => 'Mumbai',     'state_id' => 21, 'gst_type' => 'local',      'mobile' => '9876500001'],
            ['name' => 'Bharat Enterprises',      'city' => 'Delhi',      'state_id' => 7,  'gst_type' => 'interstate', 'mobile' => '9876500002'],
            ['name' => 'Chandra Industries',      'city' => 'Ahmedabad',  'state_id' => 11, 'gst_type' => 'interstate', 'mobile' => '9876500003'],
            ['name' => 'Deepak Stores',           'city' => 'Pune',       'state_id' => 21, 'gst_type' => 'local',      'mobile' => '9876500004'],
            ['name' => 'Evergreen Exports',       'city' => 'Surat',      'state_id' => 11, 'gst_type' => 'interstate', 'mobile' => '9876500005'],
            ['name' => 'Falcon Commerce',         'city' => 'Bangalore',  'state_id' => 16, 'gst_type' => 'interstate', 'mobile' => '9876500006'],
            ['name' => 'Global Mart',             'city' => 'Hyderabad',  'state_id' => 2,  'gst_type' => 'interstate', 'mobile' => '9876500007'],
            ['name' => 'Horizon Trading Co.',     'city' => 'Chennai',    'state_id' => 31, 'gst_type' => 'interstate', 'mobile' => '9876500008'],
            ['name' => 'Indus Distributors',      'city' => 'Jaipur',     'state_id' => 27, 'gst_type' => 'interstate', 'mobile' => '9876500009'],
            ['name' => 'Jubilee Retail',          'city' => 'Nagpur',     'state_id' => 21, 'gst_type' => 'local',      'mobile' => '9876500010'],
        ];

        foreach ($customers as $i => $c) {
            Account::firstOrCreate(
                ['company_id' => $cid, 'code' => 17000 + $i],
                [
                    'uuid'             => Str::uuid(),
                    'name'             => $c['name'],
                    'print_name'       => $c['name'],
                    'account_group_id' => $grp('170'),
                    'party_type'       => 'customer',
                    'gst_type'         => $c['gst_type'],
                    'city'             => $c['city'],
                    'state_id'         => $c['state_id'],
                    'country_id'       => 1,
                    'mobile_number'    => $c['mobile'],
                    'is_billwise'      => true,
                    'is_hidden'        => false,
                    'is_system'        => false,
                    'status'           => true,
                    'created_by'       => $adminUser,
                ]
            );
        }
        // Backfill related records for ALL accounts missing them across all companies
        Account::each(function (Account $customers) {
            AccountPreference::firstOrCreate(['account_id' => $customers->id]);
            AccountBankDetail::firstOrCreate(['account_id' => $customers->id]);
            AccountTaxDetail::firstOrCreate(['account_id' => $customers->id]);
        });



        // ─── DUMMY SUPPLIERS ─────────────────────────────────────────────────────────

        $suppliers = [
            ['name' => 'Krishna Suppliers',       'city' => 'Mumbai',   'state_id' => 21, 'gst_type' => 'local',      'mobile' => '9876501001'],
            ['name' => 'Lakshmi Raw Materials',   'city' => 'Delhi',    'state_id' => 7,  'gst_type' => 'interstate', 'mobile' => '9876501002'],
            ['name' => 'Mahesh Wholesalers',       'city' => 'Surat',    'state_id' => 11, 'gst_type' => 'interstate', 'mobile' => '9876501003'],
            ['name' => 'National Provisions',     'city' => 'Pune',     'state_id' => 21, 'gst_type' => 'local',      'mobile' => '9876501004'],
            ['name' => 'Om Shanti Agencies',      'city' => 'Vadodara', 'state_id' => 11, 'gst_type' => 'interstate', 'mobile' => '9876501005'],
            ['name' => 'Pioneer Supplies',        'city' => 'Kolkata',  'state_id' => 35, 'gst_type' => 'interstate', 'mobile' => '9876501006'],
            ['name' => 'Quality Products Ltd.',   'city' => 'Patna',    'state_id' => 5,  'gst_type' => 'interstate', 'mobile' => '9876501007'],
            ['name' => 'Raj Distributors',        'city' => 'Lucknow',  'state_id' => 33, 'gst_type' => 'interstate', 'mobile' => '9876501008'],
            ['name' => 'Star Import House',       'city' => 'Kochi',    'state_id' => 17, 'gst_type' => 'interstate', 'mobile' => '9876501009'],
            ['name' => 'Trimurti Traders',        'city' => 'Indore',   'state_id' => 20, 'gst_type' => 'interstate', 'mobile' => '9876501010'],
        ];

        foreach ($suppliers as $i => $s) {
            Account::firstOrCreate(
                ['company_id' => $cid, 'code' => 27100 + $i],
                [
                    'uuid'             => Str::uuid(),
                    'name'             => $s['name'],
                    'print_name'       => $s['name'],
                    'account_group_id' => $grp('270'),
                    'party_type'       => 'supplier',
                    'gst_type'         => $s['gst_type'],
                    'city'             => $s['city'],
                    'state_id'         => $s['state_id'],
                    'country_id'       => 1,
                    'mobile_number'    => $s['mobile'],
                    'is_billwise'      => true,
                    'is_hidden'        => false,
                    'is_system'        => false,
                    'status'           => true,
                    'created_by'       => $adminUser,
                ]
            );
        }

        // Backfill related records for ALL accounts missing them across all companies
        Account::each(function (Account $suppliers) {
            AccountPreference::firstOrCreate(['account_id' => $suppliers->id]);
            AccountBankDetail::firstOrCreate(['account_id' => $suppliers->id]);
            AccountTaxDetail::firstOrCreate(['account_id' => $suppliers->id]);
        });


        // ─── ITEM GROUPS ─────────────────────────────────────────────────────────────

        $itemGroupNames = [
            'Dairy Products', 'Agro Commodities', 'Packaging Materials', 'Chemicals', 'Finished Goods',
        ];

        $itemGroupMap = [];
        foreach ($itemGroupNames as $name) {
            $ig = ItemGroup::firstOrCreate(
                ['company_id' => $cid, 'name' => $name],
                [
                    'uuid'       => Str::uuid(),
                    'is_system'  => false,
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
            $itemGroupMap[$name] = $ig->id;
        }

        // ─── ITEMS (50 dummy items) ───────────────────────────────────────────────────

        $stLocal5   = $saleTypeMap['Local - 5%']     ?? null;
        $stCentral5 = $saleTypeMap['Central - 5%']   ?? null;
        $ptLocal5   = $purchaseTypeMap['Local - 5%']  ?? null;
        $ptCentral5 = $purchaseTypeMap['Central - 5%'] ?? null;

        $items = [
            // Dairy Products
            ['name' => 'Full Cream Milk',          'sku' => 'DAI-001', 'group' => 'Dairy Products',      'unit' => 'Liter',    'tax' => 1000, 'hsn' => '0401'],
            ['name' => 'Skimmed Milk',             'sku' => 'DAI-002', 'group' => 'Dairy Products',      'unit' => 'Liter',    'tax' => 1000, 'hsn' => '0401'],
            ['name' => 'Butter',                   'sku' => 'DAI-003', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0405'],
            ['name' => 'Paneer',                   'sku' => 'DAI-004', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0406'],
            ['name' => 'Ghee',                     'sku' => 'DAI-005', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0405'],
            ['name' => 'Curd',                     'sku' => 'DAI-006', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '0403'],
            ['name' => 'Buttermilk',               'sku' => 'DAI-007', 'group' => 'Dairy Products',      'unit' => 'Liter',    'tax' => 1000, 'hsn' => '0403'],
            ['name' => 'Cheese Cheddar',           'sku' => 'DAI-008', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0406'],
            ['name' => 'Whey Powder',              'sku' => 'DAI-009', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0404'],
            ['name' => 'Condensed Milk',           'sku' => 'DAI-010', 'group' => 'Dairy Products',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0402'],
            // Agro Commodities
            ['name' => 'Wheat',                    'sku' => 'AGR-001', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '1001'],
            ['name' => 'Rice (Basmati)',            'sku' => 'AGR-002', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '1006'],
            ['name' => 'Maize',                    'sku' => 'AGR-003', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '1005'],
            ['name' => 'Soybean',                  'sku' => 'AGR-004', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '1201'],
            ['name' => 'Groundnut',                'sku' => 'AGR-005', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '1202'],
            ['name' => 'Sunflower Seeds',          'sku' => 'AGR-006', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '1206'],
            ['name' => 'Cotton Seed',              'sku' => 'AGR-007', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '1207'],
            ['name' => 'Sugarcane',                'sku' => 'AGR-008', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '1212'],
            ['name' => 'Turmeric Powder',          'sku' => 'AGR-009', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0910'],
            ['name' => 'Coriander Seeds',          'sku' => 'AGR-010', 'group' => 'Agro Commodities',    'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '0909'],
            // Packaging Materials
            ['name' => 'HDPE Bag 25 Kg',           'sku' => 'PKG-001', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '3923'],
            ['name' => 'PP Woven Bag 50 Kg',       'sku' => 'PKG-002', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '6305'],
            ['name' => 'Carton Box (Large)',        'sku' => 'PKG-003', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '4819'],
            ['name' => 'Carton Box (Small)',        'sku' => 'PKG-004', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '4819'],
            ['name' => 'Stretch Film Roll',        'sku' => 'PKG-005', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '3920'],
            ['name' => 'Shrink Wrap',              'sku' => 'PKG-006', 'group' => 'Packaging Materials', 'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '3920'],
            ['name' => 'Label Sticker Sheet',      'sku' => 'PKG-007', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '4821'],
            ['name' => 'Foam Sheet',               'sku' => 'PKG-008', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '3921'],
            ['name' => 'Wooden Pallet',            'sku' => 'PKG-009', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1006, 'hsn' => '4415'],
            ['name' => 'Jute Bag 40 Kg',           'sku' => 'PKG-010', 'group' => 'Packaging Materials', 'unit' => 'Piece',    'tax' => 1001, 'hsn' => '6305'],
            // Chemicals
            ['name' => 'Caustic Soda',             'sku' => 'CHM-001', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '2815'],
            ['name' => 'Hydrochloric Acid',        'sku' => 'CHM-002', 'group' => 'Chemicals',           'unit' => 'Liter',    'tax' => 1006, 'hsn' => '2806'],
            ['name' => 'Sodium Chloride',          'sku' => 'CHM-003', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '2501'],
            ['name' => 'Citric Acid',              'sku' => 'CHM-004', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '2918'],
            ['name' => 'Hydrogen Peroxide',        'sku' => 'CHM-005', 'group' => 'Chemicals',           'unit' => 'Liter',    'tax' => 1006, 'hsn' => '2847'],
            ['name' => 'Calcium Carbonate',        'sku' => 'CHM-006', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '2836'],
            ['name' => 'Potassium Chloride',       'sku' => 'CHM-007', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '3104'],
            ['name' => 'Urea (Industrial)',        'sku' => 'CHM-008', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1000, 'hsn' => '3102'],
            ['name' => 'Ferrous Sulphate',         'sku' => 'CHM-009', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '2833'],
            ['name' => 'Activated Carbon',         'sku' => 'CHM-010', 'group' => 'Chemicals',           'unit' => 'Kilogram', 'tax' => 1006, 'hsn' => '3802'],
            // Finished Goods
            ['name' => 'Milk Powder (SMP)',        'sku' => 'FG-001',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0402'],
            ['name' => 'Milk Powder (WMP)',        'sku' => 'FG-002',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0402'],
            ['name' => 'Casein Powder',            'sku' => 'FG-003',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '3501'],
            ['name' => 'Lactose Powder',           'sku' => 'FG-004',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '1702'],
            ['name' => 'Processed Butter',         'sku' => 'FG-005',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0405'],
            ['name' => 'Cream (35% Fat)',          'sku' => 'FG-006',  'group' => 'Finished Goods',      'unit' => 'Liter',    'tax' => 1004, 'hsn' => '0401'],
            ['name' => 'Whey Concentrate',         'sku' => 'FG-007',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0404'],
            ['name' => 'Anhydrous Milk Fat',       'sku' => 'FG-008',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0405'],
            ['name' => 'Sodium Caseinate',         'sku' => 'FG-009',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '3501'],
            ['name' => 'Milk Protein Concentrate', 'sku' => 'FG-010',  'group' => 'Finished Goods',      'unit' => 'Kilogram', 'tax' => 1004, 'hsn' => '0404'],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['company_id' => $cid, 'sku' => $item['sku']],
                [
                    'uuid'                        => Str::uuid(),
                    'name'                        => $item['name'],
                    'print_name'                  => $item['name'],
                    'item_group_id'               => $itemGroupMap[$item['group']] ?? null,
                    'unit_id'                     => $unitMap[$item['unit']] ?? null,
                    'tax_category_id'             => $taxMap[$item['tax']] ?? null,
                    'hsn_sac_code'                => $item['hsn'],
                    'sale_type_local_id'          => $stLocal5,
                    'sale_type_interstate_id'     => $stCentral5,
                    'purchase_type_local_id'      => $ptLocal5,
                    'purchase_type_interstate_id' => $ptCentral5,
                    'is_maintain_stock_balance'   => true,
                    'status'                      => true,
                    'created_by'                  => $adminUser,
                ]
            );
        }

        // ─── CONDITIONS ──────────────────────────────────────────────────────────────

        $conditionNames = [
            'Fresh', 'Aged', 'Frozen', 'Chilled', 'Dried',
            'Processed', 'Raw', 'Organic', 'Conventional', 'Premium Grade',
        ];

        foreach ($conditionNames as $name) {
            Condition::firstOrCreate(
                ['company_id' => $cid, 'name' => $name],
                [
                    'uuid'       => Str::uuid(),
                    'print_name' => $name,
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
        }

        // ─── DESTINATIONS ────────────────────────────────────────────────────────────

        $destinations = [
            ['name' => 'Mumbai Depot',         'city' => 'Mumbai',     'district' => 'Mumbai',    'state_id' => 21, 'kms' => 0],
            ['name' => 'Delhi Warehouse',      'city' => 'Delhi',      'district' => 'New Delhi', 'state_id' => 7,  'kms' => 1400],
            ['name' => 'Ahmedabad Hub',        'city' => 'Ahmedabad',  'district' => 'Ahmedabad', 'state_id' => 11, 'kms' => 530],
            ['name' => 'Bangalore Cold Store', 'city' => 'Bangalore',  'district' => 'Bangalore', 'state_id' => 16, 'kms' => 980],
            ['name' => 'Chennai Port',         'city' => 'Chennai',    'district' => 'Chennai',   'state_id' => 31, 'kms' => 1340],
            ['name' => 'Kolkata Storage',      'city' => 'Kolkata',    'district' => 'Kolkata',   'state_id' => 35, 'kms' => 2050],
            ['name' => 'Hyderabad Centre',     'city' => 'Hyderabad',  'district' => 'Hyderabad', 'state_id' => 2,  'kms' => 710],
            ['name' => 'Pune Facility',        'city' => 'Pune',       'district' => 'Pune',      'state_id' => 21, 'kms' => 160],
            ['name' => 'Jaipur Depot',         'city' => 'Jaipur',     'district' => 'Jaipur',    'state_id' => 27, 'kms' => 1170],
            ['name' => 'Surat Warehouse',      'city' => 'Surat',      'district' => 'Surat',     'state_id' => 11, 'kms' => 285],
        ];

        foreach ($destinations as $d) {
            Destination::firstOrCreate(
                ['company_id' => $cid, 'name' => $d['name']],
                [
                    'uuid'       => Str::uuid(),
                    'city'       => $d['city'],
                    'district'   => $d['district'],
                    'state_id'   => $d['state_id'],
                    'country_id' => 1,
                    'kms'        => $d['kms'],
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
        }

        // ─── GODOWNS ─────────────────────────────────────────────────────────────────

        $godownNames = [
            'Main Godown', 'Cold Storage Unit A', 'Dry Store Room', 'Dispatch Bay', 'Raw Material Store',
        ];

        foreach ($godownNames as $name) {
            Godown::firstOrCreate(
                ['company_id' => $cid, 'godown_name' => $name],
                [
                    'uuid'       => Str::uuid(),
                    'status'     => true,
                    'created_by' => $adminUser,
                ]
            );
        }
    }
}
