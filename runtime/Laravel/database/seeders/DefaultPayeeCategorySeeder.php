<?php

namespace Database\Seeders;

use App\Models\PayeeCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class DefaultPayeeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $payeeCategories = [
            ['name' => 'Individual - Residents', 'code'=> 401],
            ['name' => 'Individual - Non Residents', 'code' => 402],
            ['name' => 'Domestic Company', 'code' => 403],
            ['name' => 'Foreign Company', 'code' => 404],
            ['name' => 'Hindu Undivided Family', 'code' => 405],
            ['name' => 'Partnership Firm', 'code' => 406],
            ['name' => 'Association of Persons', 'code' => 407],
            ['name' => 'Body of Individuals', 'code' => 408],
            ['name' => 'Co-operative Society', 'code' => 409],
            ['name' => 'Trust', 410],
        ];       

        $payeeCategories = array_map(function ($category) {
            $category['slug'] = Str::slug($category['name']);
            return $category;
        }, $payeeCategories);

        DB::table('default_payee_categories')->upsert(
            $payeeCategories,
            ['slug'],
            ['name', 'code']
        );
    }
}

