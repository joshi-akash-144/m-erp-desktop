<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultBillSundrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultBillSundries = [
            [
                'name' => 'CD',
                'bill_sundry_type' => 'subtractive',
                'calculation_type' => 'percentage',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 41000, // Cash Discount Received


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS
                'sale_adjust_in_amount' => true,
                'sale_account_type' => null,
                'sale_account_code' => null,


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 1,

                'is_read_only' => false,
                'code'  => 1001

            ],
            [
                'name' => 'CGST',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'percentage',
                'bill_sundry_amount_round_off' => false,
                'bill_sundry_nature' => 'gst',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 26000, // CGST INPUT


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 26001, // CGST OUTPUT


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => true,
                'sales_preload_order' => 2,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 2,

                'is_read_only' => true,
                'code'  => 1002

            ],
            [
                'name' => 'SGST',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'percentage',
                'bill_sundry_amount_round_off' => false,
                'bill_sundry_nature' => 'gst',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 26002, // SGST INPUT


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 26003, // SGST OUTPUT


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => true,
                'sales_preload_order' => 1,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 3,
                'is_read_only' => true,
                'code'  => 1003
            ],
            [
                'name' => 'IGST',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'percentage',
                'bill_sundry_amount_round_off' => false,
                'bill_sundry_nature' => 'gst',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 26004, // IGST INPUT


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 26005, // IGST OUTPUT


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => true,
                'sales_preload_order' => 3,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 4,
                'is_read_only' => true,
                'code'  => 1004

            ],
            [
                'name' => 'Freight',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 44000, // Freight 


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 44000, // Freight


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => true,
                'sales_preload_order' => 4,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 5,
                'is_read_only' => false,
                'code'  => 1005

            ],
            [
                'name' => 'Labour',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 44003, // Labour Charges


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 44003, //  Labour Charges


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => true,
                'sales_preload_order' => 5,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 6,
                'is_read_only' => false,
                'code'  => 1006
            ],
            [
                'name' => 'Penalty',
                'bill_sundry_type' => 'subtractive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 41001, // Penalty Received (Indirect Income)


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => true,
                'sale_account_type' => null,
                'sale_account_code' => null,


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 7,
                'is_read_only' => false,
                'code'  => 1007
            ],
            [
                'name' => 'Rebate',
                'bill_sundry_type' => 'subtractive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 41002, // Rebate Received (Indirect Income)


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => true,
                'sale_account_type' => null,
                'sale_account_code' => null,


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 8,
                'is_read_only' => false,
                'code'  => 1008
            ],
            [
                'name' => 'TDS(Purchase of Goods)',
                'bill_sundry_type' => 'subtractive',
                'calculation_type' => 'percentage',
                'bill_sundry_amount_round_off' => true,
                'bill_sundry_nature' => 'tds',
                'apply_on' => 'running_total',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 26009, // TDS On Purchase of Goods


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => true,
                'sale_account_type' => null,
                'sale_account_code' => null,


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 9,
                'is_read_only' => false,
                'code'  => 1009
            ],
            [
                'name' => 'Round Off(+)',
                'bill_sundry_type' => 'additive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => false,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 42001, // Round Off (Indirect Income)


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null,
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 42001, // Round Off (Indirect Income)


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 10,
                'is_read_only' => false,
                'code'  => 1010
            ],
            [
                'name' => 'Round Off(-)',
                'bill_sundry_type' => 'subtractive',
                'calculation_type' => 'fixed',
                'bill_sundry_amount_round_off' => false,
                'bill_sundry_nature' => 'other',
                'apply_on' => 'basic',
                // 'affect_grand_total' => false,
                'default_value' => 0,


                // PURCHASE SETTINGS                
                'purchase_adjust_in_amount' => false,
                'purchase_account_type' => 'specify_account',
                'purchase_account_code' => 42001,


                'purchase_adjust_in_party_amount' => true,
                'purchase_party_account_type' => null,
                'purchase_party_account_code' => null, // Round Off (Indirect Income)
                'purchase_post_over_and_above' => false,

                // SALE SETTINGS                
                'sale_adjust_in_amount' => false,
                'sale_account_type' => 'specify_account',
                'sale_account_code' => 42001,


                'sale_adjust_in_party_amount' => true,
                'sale_party_account_type' => null,
                'sale_party_account_code' => null,
                'sale_post_over_and_above' => false,

                'preload_in_sales' => false,
                'sales_preload_order' => 0,
                'preload_in_purchases' => true,
                'purchases_preload_order' => 11,
                'is_read_only' => false,
                'code'  => 1011
            ],

        ];


        foreach ($defaultBillSundries as $sundry) {
            DB::table('default_bill_sundries')->updateOrInsert(
                ['code' => $sundry['code']],
                $sundry
            );
        }
    }
}
