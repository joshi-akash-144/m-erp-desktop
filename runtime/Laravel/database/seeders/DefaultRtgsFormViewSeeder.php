<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RtgsFormView;

class DefaultRtgsFormViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $rtgsData = [
            [
                'name' => 'Default RTGS Form',
                'view_name' => 'bank-default-rtgs',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BOB RTGS Form',
                'view_name' => 'bob-rtgs-form',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rtgsData as $key => $data) {
            $check = RtgsFormView::where('name', $data['name'])->first();
            if (empty($check)) {
                $check = new RtgsFormView();
            }
            $check->name = $data['name'];
            $check->view_name = $data['view_name'];
            $check->is_default = $data['is_default'];
            $check->created_at = $data['created_at'];
            $check->updated_at = $data['updated_at'];
            $check->save();
        }
    }
}

