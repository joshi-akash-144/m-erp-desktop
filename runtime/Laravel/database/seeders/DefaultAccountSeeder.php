<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DefaultAccountSeeder extends Seeder
{
    public function run()
    {
        $accounts = [

            // Cash & Bank
            // ['name' => 'Cash', 'party_type' => 'account', 'group_code' => 120],
            // ['name' => 'Petty Cash', 'party_type' => 'account', 'group_code' => 120],
            // // ['name' => 'Bank A/C - SBI', 'party_type' => 'account', 'group_code' => 130],
            // // ['name' => 'Bank A/C - HDFC', 'party_type' => 'account', 'group_code' => 130],

            // ['name' => 'Stock in Hand', 'party_type' => 'account', 'group_code' => 140],
            // 
            ['name' => 'Opening Balance Difference', 'party_type' => 'account', 'group_code' => 350, 'is_hidden' => true],
            // // Sales
            // ['name' => 'Sale', 'party_type' => 'account', 'group_code' => 390],
            ['name' => 'Export Sales', 'party_type' => 'account', 'group_code' => 390, 'is_hidden' => false],
            ['name' => 'Local Sales', 'party_type' => 'account', 'group_code' => 390, 'is_hidden' => false],
        
            // // Purchase
            // ['name' => 'Purchase', 'party_type' => 'account', 'group_code' => 450],
            ['name' => 'Import Purchase', 'party_type' => 'account', 'group_code' => 450,'is_hidden' => false],
            ['name' => 'Local Purchase', 'party_type' => 'account', 'group_code' => 450, 'is_hidden' => false],
        
            // // GST Input / Output
            // ['name' => 'CGST', 'party_type' => 'account', 'group_code' => 260],
            // ['name' => 'SGST', 'party_type' => 'account', 'group_code' => 260],
            // ['name' => 'IGST', 'party_type' => 'account', 'group_code' => 260],
            ['name' => 'CGST Input', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'CGST Output', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'SGST Input', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'SGST Output', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'IGST Input', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'IGST Output', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'GST RCM Payable', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'GST RCM Receivable', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],

        
            // // Sundry Debtors / Creditors
            // // ['name' => 'Sundry Debtors - Default', 'party_type' => 'account', 'group_code' => 170],
            // // ['name' => 'Sundry Creditors - Default', 'party_type' => 'account', 'group_code' => 270],
        
            // // Freight & Additional Charges
            ['name' => 'Freight Charges', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
            ['name' => 'Transport Expenses', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
            ['name' => 'Packing Charges', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
        
            // // Rounding & Discounts
            // ['name' => 'Round Off', 'party_type' => 'account', 'group_code' => 360],
            // ['name' => 'CD', 'party_type' => 'account', 'group_code' => 410],
            ['name' => 'Cash Discount Received', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
            ['name' => 'Penalty Received (Indirect Income)', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
            ['name' => 'Rebate Received (Indirect Income)', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
            ['name' => 'Cash Discount Allowed', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
            ['name' => 'Round Off', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false], 
        
            // // Indirect Expenses
            // // ['name' => 'Office Expenses', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
            // ['name' => 'Rent Expenses', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
            // ['name' => 'Telephone Expenses', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
            // ['name' => 'Electricity Expenses', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
            // ['name' => 'Salary Expenses', 'party_type' => 'account', 'group_code' => 420, 'is_hidden' => false],
        
            // // Direct Expenses
            ['name' => 'Labour Charges', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
            ['name' => 'Factory Expenses', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
            ['name' => 'Power & Fuel', 'party_type' => 'account', 'group_code' => 440, 'is_hidden' => false],
        
            // // Income Accounts
            // ['name' => 'Commission Received', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
            // ['name' => 'Interest Received', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
            // ['name' => 'Misc Income', 'party_type' => 'account', 'group_code' => 410, 'is_hidden' => false],
        
            // // Loans
            // ['name' => 'Secured Loan - Bank', 'party_type' => 'account', 'group_code' => 320, 'is_hidden' => false],
            // ['name' => 'Unsecured Loan - Others', 'party_type' => 'account', 'group_code' => 330, 'is_hidden' => false],
        
            // // Capital
            // ['name' => 'Capital Account', 'party_type' => 'account', 'group_code' => 340, 'is_hidden' => false],
            // ['name' => 'Drawings', 'party_type' => 'account', 'group_code' => 340, 'is_hidden' => false],
        
            // // TDS
            // ['name' => 'TDS', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            // ['name' => 'TCS', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'TDS Payable', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'TDS on Purchase of Goods', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
            ['name' => 'TCS Payable', 'party_type' => 'account', 'group_code' => 260, 'is_hidden' => false],
        
            // // Suspense
            // ['name' => 'Suspense Account', 'party_type' => 'account', 'group_code' => 460, 'is_hidden' => false],
        
            // // Broker Example
            // ['name' => 'Self', 'party_type' => 'broker', 'group_code' => 270, 'is_hidden' => false],

            ['name' => 'Opening Stock Account', 'party_type' => 'account', 'group_code' => 340, 'is_hidden' => true],
            ['name' => 'Freights Income', 'party_type' => 'account', 'group_code' => 400, 'is_hidden' => false],
            
        ];
        
        $accounts = array_map(function ($account) {
            $account['slug'] = Str::slug($account['name']);
            return $account;
        }, $accounts);

        DB::table('default_accounts')->upsert(
            $accounts,
            ['slug'],
            ['name','group_code','party_type']
        );
    }
}
