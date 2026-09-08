<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('countries')->updateOrInsert(
            ['code' => 'IND'],
            [
                'name'       => 'India',
                'gst_code'   => 1,    
                'status'     => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
