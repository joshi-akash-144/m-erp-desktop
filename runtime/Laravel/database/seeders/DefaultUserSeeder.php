<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure Roles exist
        $rootRole = Role::updateOrCreate(['name' => 'Root'], ['guard_name' => 'web']);
        $superAdminRole = Role::updateOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $adminRole = Role::updateOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);

        // Ensure Root User
        $rootUser = User::updateOrCreate(
            ['email' => 'root@gmail.com'],
            [
                'name' => 'System Root',
                'uuid' => Str::uuid(),
                'username' => 'root',
                'password' => Hash::make('root@123'),
                'is_approved' => true,
                'is_default' => true,
            ]
        );
        try {
            $rootUser->assignRole($rootRole);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ignore race condition 
        }

        // Ensure Super Admin User
        $superAdminUser = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'System Super Admin',
                'uuid' => Str::uuid(),
                'username' => 'super_admin',
                'password' => Hash::make('super@123'),
                'is_approved' => true,
                'is_default' => true,
            ]
        );
        try {
            $superAdminUser->assignRole($superAdminRole);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ignore race condition 
        }

        // Ensure Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Admin',
                'uuid' => Str::uuid(),
                'username' => 'admin',
                'password' => Hash::make('admin@123'),
                'is_approved' => true,
                'is_default' => true,
            ]
        );
        try {
            $adminUser->assignRole($adminRole);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ignore race condition 
        }

    }
}
