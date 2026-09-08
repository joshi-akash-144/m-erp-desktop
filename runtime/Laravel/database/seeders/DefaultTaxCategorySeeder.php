<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DefaultTaxCategorySeeder extends Seeder
{
    public function run(): void
    {
        $taxCategories = [
            // Goods
            ['name' => 'Goods Exempt',       'type' => 'goods',    'zero_tax_type' => 'exempt',     'code' => 1000, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Goods Nil Rated',    'type' => 'goods',    'zero_tax_type' => 'nil_rated',  'code' => 1001, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Goods Zero Rated',   'type' => 'goods',    'zero_tax_type' => 'zero_rated', 'code' => 1002, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Goods Non-GST',      'type' => 'goods',    'zero_tax_type' => 'non_gst',    'code' => 1003, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Goods 5%',           'type' => 'goods',    'zero_tax_type' => null,         'code' => 1004, 'cgst' => 2.50, 'sgst' => 2.50, 'igst' => 5.00],
            ['name' => 'Goods 12%',          'type' => 'goods',    'zero_tax_type' => null,         'code' => 1005, 'cgst' => 6.00, 'sgst' => 6.00, 'igst' => 12.00],
            ['name' => 'Goods 18%',          'type' => 'goods',    'zero_tax_type' => null,         'code' => 1006, 'cgst' => 9.00, 'sgst' => 9.00, 'igst' => 18.00],
            ['name' => 'Goods 28%',          'type' => 'goods',    'zero_tax_type' => null,         'code' => 1007, 'cgst' => 14.00,'sgst' => 14.00,'igst' => 28.00],

            // Services
            ['name' => 'Services Exempt',    'type' => 'services','zero_tax_type' => 'exempt',     'code' => 2000, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Services Nil Rated', 'type' => 'services','zero_tax_type' => 'nil_rated',  'code' => 2001, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Services Zero Rated','type' => 'services','zero_tax_type' => 'zero_rated', 'code' => 2002, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Services Non-GST',   'type' => 'services','zero_tax_type' => 'non_gst',    'code' => 2003, 'cgst' => 0.00,  'sgst' => 0.00,  'igst' => 0.00],
            ['name' => 'Services 5%',        'type' => 'services','zero_tax_type' => null,         'code' => 2004, 'cgst' => 2.50, 'sgst' => 2.50, 'igst' => 5.00],
            ['name' => 'Services 12%',       'type' => 'services','zero_tax_type' => null,         'code' => 2005, 'cgst' => 6.00, 'sgst' => 6.00, 'igst' => 12.00],
            ['name' => 'Services 18%',       'type' => 'services','zero_tax_type' => null,         'code' => 2006, 'cgst' => 9.00, 'sgst' => 9.00, 'igst' => 18.00],
            ['name' => 'Services 28%',       'type' => 'services','zero_tax_type' => null,         'code' => 2007, 'cgst' => 14.00,'sgst' => 14.00,'igst' => 28.00],
        ];

        $taxCategories = array_map(function ($taxCategory) {
            $taxCategory['slug'] = Str::slug($taxCategory['name']);
            return $taxCategory;
        }, $taxCategories);


        foreach ($taxCategories as $category) {
            DB::table('default_tax_categories')->updateOrInsert(
                ['code' => $category['code']], 
                $category
            );
        }
    }
}
