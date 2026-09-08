<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultVoucherTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['id' => 1,  'name' => 'Journal',              'code' => 'JR',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 2,  'name' => 'Payment',              'code' => 'PMT',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 3,  'name' => 'Receipt',              'code' => 'RCT',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 4,  'name' => 'Purchase Invoice',     'code' => 'PI',    'affects_inventory' => 1, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 5,  'name' => 'Sales Invoice',        'code' => 'SI',    'affects_inventory' => 1, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 6,  'name' => 'Sales Order',          'code' => 'SO',    'affects_inventory' => 0, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 7,  'name' => 'Purchase Order',       'code' => 'PO',    'affects_inventory' => 0, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 8,  'name' => 'Goods Receipt Note',   'code' => 'GRN',   'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 9,  'name' => 'Purchase Return(Debit Note)',           'code' => 'PRDN',    'affects_inventory' => 1, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 10, 'name' => 'Sales Return(Credit Note)',          'code' => 'SRCN',    'affects_inventory' => 1, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 11, 'name' => 'Contra',               'code' => 'CON',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 12, 'name' => 'Expense',              'code' => 'EXP',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 13, 'name' => 'Income',               'code' => 'INC',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 14, 'name' => 'Advance Payment',      'code' => 'AP',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 15, 'name' => 'Advance Receipt',      'code' => 'AR',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 16, 'name' => 'Bank Reconciliation',  'code' => 'BR',    'affects_inventory' => 0, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 17, 'name' => 'Opening Balance',      'code' => 'OB',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 18, 'name' => 'Inventory Adjustment', 'code' => 'IA',    'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 19, 'name' => 'Payroll',              'code' => 'PAY',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 20, 'name' => 'Tax Payment',          'code' => 'TXP',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 21, 'name' => 'Tax Receipt',          'code' => 'TXR',   'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 22, 'name' => 'Petty Cash',           'code' => 'PC',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 23, 'name' => 'Contra Receipt',       'code' => 'CR',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 24, 'name' => 'Contra Payment',       'code' => 'CP',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 25, 'name' => 'Goods Issue Note',     'code' => 'GIN',   'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 26, 'name' => 'Stock',                'code' => 'STK',   'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 27, 'name' => 'Delivery Challan',     'code' => 'DC',    'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 28, 'name' => 'Opening Stock',         'code' => 'OPS',    'affects_inventory' => 1, 'affects_accounts' => 0, 'status' => 1],
            ['id' => 29, 'name' => 'Credit Note',           'code' => 'CN',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
            ['id' => 30, 'name' => 'Debit Note',           'code' => 'DN',    'affects_inventory' => 0, 'affects_accounts' => 1, 'status' => 1],
        ];

        foreach ($types as $type) {
            DB::table('voucher_types')->updateOrInsert(
                ['id' => $type['id']],   // Unique key
                array_merge($type, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
