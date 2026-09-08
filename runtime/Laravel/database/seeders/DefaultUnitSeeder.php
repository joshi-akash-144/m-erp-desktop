<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DefaultUnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'Kilogram', 'uqc' => 'KGS', 'code' => '1000'],
            ['name' => 'Gram', 'uqc' => 'GMS', 'code' => '1002'],
            ['name' => 'Liter', 'uqc' => 'LTR', 'code' => '1003'],
            ['name' => 'Milliliter', 'uqc' => 'MLT', 'code' => '1004'],
            ['name' => 'Piece', 'uqc' => 'PCS', 'code' => '1005'],
            ['name' => 'Meter', 'uqc' => 'MTR', 'code' => '1006'],
            ['name' => 'Centimeter', 'uqc' => 'CMT', 'code' => '1007'],
            ['name' => 'Nos', 'uqc' => 'NOS', 'code' => '1008'],
            ['name' => 'Ton', 'uqc' => 'TON', 'code' => '1009'],
        ];
        
        $units = array_map(function ($unit) {
            $unit['slug'] = Str::slug($unit['name']);
            return $unit;
        }, $units);


        foreach ($units as $unit) {
            DB::table('default_units')->updateOrInsert(
                ['name' => $unit['name']],
                $unit
            );
        }
    }
}
