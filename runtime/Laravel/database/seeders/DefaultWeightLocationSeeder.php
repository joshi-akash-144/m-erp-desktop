<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultWeightLocationSeeder extends Seeder
{

    public function run(): void
    {
        $weightLocations = [
            ['godown_name' => 'Kankrol Godown', 'ip_address' => '139.167.245.82' , 'url' =>'http://139.167.245.82:8732/WEIGHT/'],
            ['godown_name' => 'Viravada Godown', 'ip_address' => '139.167.245.122' , 'url' =>'http://139.167.245.122:8732/WEIGHT/'],
        ];

        foreach ($weightLocations as $weightLocation) {
            DB::table('weight_locations')->updateOrInsert(
                [
                    'godown_name' => $weightLocation['godown_name'],
                    'ip_address'  => $weightLocation['ip_address'],
                ],
                [
                    'url'         => $weightLocation['url'],
                    'created_at'  => now(),
                    'updated_at'  => now()
                ]
            );
        }
    }

}