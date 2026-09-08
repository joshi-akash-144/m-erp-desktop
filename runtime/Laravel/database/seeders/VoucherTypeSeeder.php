<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherTypeSeeder extends Seeder
{
    public function run(): void
    {
        $voucherTypes = [
            ['name' => 'Journal', 'code' => 'JRN'],              // General Journal Entry
            ['name' => 'Payment', 'code' => 'PAY'],              // Cash/Bank Payment
            ['name' => 'Receipt', 'code' => 'REC'],              // Cash/Bank Receipt
            ['name' => 'Purchase', 'code' => 'PUR'],             // Purchase Invoice
            ['name' => 'Sale', 'code' => 'SAL'],                 // Sales Invoice
            ['name' => 'Sale Order', 'code' => 'SO'],            // Sales Order
            ['name' => 'Purchase Order', 'code' => 'PO'],        // Purchase Order
            ['name' => 'Goods Receipt Note', 'code' => 'GRN'],   // Goods Receipt (Stock IN)
            ['name' => 'Debit Note', 'code' => 'DBN'],           // Purchase Return / Debit Adjustment
            ['name' => 'Credit Note', 'code' => 'CRN'],          // Sales Return / Credit Adjustment
            ['name' => 'Contra', 'code' => 'CON'],               // Cash/Bank Transfer between accounts
            ['name' => 'Expense', 'code' => 'EXP'],              // Expense Voucher
            ['name' => 'Income', 'code' => 'INC'],               // Non-sales income
            ['name' => 'Advance Payment', 'code' => 'ADV'],      // Payment in advance to supplier
            ['name' => 'Advance Receipt', 'code' => 'ADR'],      // Receipt in advance from customer
            ['name' => 'Bank Reconciliation', 'code' => 'BR'],   // Bank Reconciliation Entry
            ['name' => 'Opening Balance', 'code' => 'OPB'],      // Opening Balance Entry
            ['name' => 'Inventory Adjustment', 'code' => 'INV'], // Stock/Inventory Adjustments
            ['name' => 'Payroll', 'code' => 'PAYR'],             // Salary/Payroll Payment
            ['name' => 'Tax Payment', 'code' => 'TAX'],          // GST/TDS/Other Tax Payment
            ['name' => 'Tax Receipt', 'code' => 'TRC'],          // Tax Refund / Recovery
            ['name' => 'Petty Cash', 'code' => 'PET'],           // Petty Cash Transactions
            ['name' => 'Contra Receipt', 'code' => 'CR'],        // Bank Transfer Receipt
            ['name' => 'Contra Payment', 'code' => 'CP'],        // Bank Transfer Payment
            ['name' => 'Goods Issue Note', 'code' => 'GIN'],     // Goods Issue (Stock OUT)
        ];
        

        foreach ($voucherTypes as $type) {
            DB::table('voucher_types')->updateOrInsert(
                ['name' => $type['name']],
                ['code' => $type['code'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
