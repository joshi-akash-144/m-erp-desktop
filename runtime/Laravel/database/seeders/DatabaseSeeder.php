<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            DefaultUserSeeder::class,
            DefaultCountrySeeder::class,
            DefaultStateSeeder::class,
            DefaultAccountGroupsSeeder::class,
            DefaultAccountSeeder::class,
            DefaultUnitSeeder::class,
            DefaultSaleTypeSeeder::class,
            DefaultPurchaseTypeSeeder::class,
            DefaultTaxCategorySeeder::class,
            DefaultPayeeCategorySeeder::class,
            DefaultTdsCategorySeeder::class,
            DefaultBillSundrySeeder::class,
            DefaultVoucherTypeSeeder::class,
            // DefaultCompanySeeder::class,
            DefaultWeightLocationSeeder::class,
            DefaultChequeStyleSeeder::class,
            DefaultRtgsFormViewSeeder::class,
            ModuleSeeder::class
        ]);
    }
    
}
