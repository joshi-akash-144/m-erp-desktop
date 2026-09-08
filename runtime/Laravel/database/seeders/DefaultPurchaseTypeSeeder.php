<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DefaultPurchaseTypeSeeder extends Seeder
{
    public function run()
    {
        $purchaseTypes = [
            ['name' => 'Local Nil Rated', 'region' => 'local', 'taxation_type' => 'nil_rated', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central Nil Rated', 'region' => 'interstate', 'taxation_type' => 'nil_rated', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local Exempted', 'region' => 'local', 'taxation_type' => 'exempt', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central Exempted', 'region' => 'interstate', 'taxation_type' => 'exempt', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local Non-GST', 'region' => 'local', 'taxation_type' => 'non_gst', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central Non-GST', 'region' => 'interstate', 'taxation_type' => 'non_gst', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local Zero Rated', 'region' => 'local', 'taxation_type' => 'zero_rated', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central Zero Rated', 'region' => 'interstate', 'taxation_type' => 'zero_rated', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local - 5%', 'region' => 'local', 'taxation_type' => 'taxable', 'cgst' => 2.50, 'sgst' => 2.50, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central - 5%', 'region' => 'interstate', 'taxation_type' => 'taxable', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 5.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local - 12%', 'region' => 'local', 'taxation_type' => 'taxable', 'cgst' => 6.00, 'sgst' => 6.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central - 12%', 'region' => 'interstate', 'taxation_type' => 'taxable', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 12.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local - 18%', 'region' => 'local', 'taxation_type' => 'taxable', 'cgst' => 9.00, 'sgst' => 9.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central - 18%', 'region' => 'interstate', 'taxation_type' => 'taxable', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 18.00, 'transaction_type' => 'domestic'],

            ['name' => 'Local - 28%', 'region' => 'local', 'taxation_type' => 'taxable', 'cgst' => 14.00, 'sgst' => 14.00, 'igst' => 0.00, 'transaction_type' => 'domestic'],
            ['name' => 'Central - 28%', 'region' => 'interstate', 'taxation_type' => 'taxable', 'cgst' => 0.00, 'sgst' => 0.00, 'igst' => 28.00, 'transaction_type' => 'domestic'],
        ];

        $purchaseTypes = array_map(function ($type) {
            $type['slug'] = Str::slug($type['name']);
            return $type;
        }, $purchaseTypes);

        DB::table('default_purchase_types')->upsert(
            $purchaseTypes,
            ['slug'],
            ['name','region','taxation_type','cgst','sgst','igst','slug']
        );


    }
}
