<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultStateSeeder extends Seeder
{
  public function run()
  {
    $states = [
      ['name' => 'Andaman & Nicobar Islands', 'code' => 'AN', 'gst_code' => 35, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Andhra Pradesh (Old)', 'code' => 'AP', 'gst_code' => 28, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Arunachal Pradesh', 'code' => 'AR', 'gst_code' => 12, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Assam', 'code' => 'AS', 'gst_code' => 18, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Bihar', 'code' => 'BR', 'gst_code' => 10, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Chandigarh', 'code' => 'CH', 'gst_code' => 4, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Chhattisgarh', 'code' => 'CG', 'gst_code' => 22, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Dadra and Nagar Haveli', 'code' => 'DN', 'gst_code' => 26, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Delhi', 'code' => 'DL', 'gst_code' => 7, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Goa', 'code' => 'GA', 'gst_code' => 30, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Gujarat', 'code' => 'GJ', 'gst_code' => 24, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Haryana', 'code' => 'HR', 'gst_code' => 6, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Himachal Pradesh', 'code' => 'HP', 'gst_code' => 2, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Jammu & Kashmir', 'code' => 'JK', 'gst_code' => 1, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Jharkhand', 'code' => 'JH', 'gst_code' => 20, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Karnataka', 'code' => 'KA', 'gst_code' => 29, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Kerala', 'code' => 'KL', 'gst_code' => 32, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Ladakh', 'code' => 'LA', 'gst_code' => 38, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Lakshadweep', 'code' => 'LD', 'gst_code' => 31, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Madhya Pradesh', 'code' => 'MP', 'gst_code' => 23, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Maharashtra', 'code' => 'MH', 'gst_code' => 27, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Manipur', 'code' => 'MN', 'gst_code' => 14, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Meghalaya', 'code' => 'ML', 'gst_code' => 17, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Mizoram', 'code' => 'MZ', 'gst_code' => 15, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Nagaland', 'code' => 'NL', 'gst_code' => 13, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Odisha', 'code' => 'OR', 'gst_code' => 21, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Puducherry', 'code' => 'PY', 'gst_code' => 34, 'country_id' => 1, 'is_union_territory' => 1],
      ['name' => 'Punjab', 'code' => 'PB', 'gst_code' => 3, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Rajasthan', 'code' => 'RJ', 'gst_code' => 8, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Sikkim', 'code' => 'SK', 'gst_code' => 11, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Tamil Nadu', 'code' => 'TN', 'gst_code' => 33, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Telangana', 'code' => 'TS', 'gst_code' => 36, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Tripura', 'code' => 'TR', 'gst_code' => 16, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Uttar Pradesh', 'code' => 'UP', 'gst_code' => 9, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Uttarakhand', 'code' => 'UK', 'gst_code' => 5, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'West Bengal', 'code' => 'WB', 'gst_code' => 19, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Andhra Pradesh', 'code' => 'AP', 'gst_code' => 37, 'country_id' => 1, 'is_union_territory' => 0],
      ['name' => 'Daman and Diu', 'code' => 'DD', 'gst_code' => 25, 'country_id' => 1, 'is_union_territory' => 1],
    ];



    foreach ($states as $state) {
      DB::table('states')->updateOrInsert(
        ['gst_code' => $state['gst_code']],
        array_merge([
          'country_id' => 1,
          'status' => true,
          'updated_at' => now(),
          'created_at' => now(),
        ], $state)
      );
    }
  }
}
