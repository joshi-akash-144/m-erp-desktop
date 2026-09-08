<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $root = Role::updateOrCreate(['name' => 'Root'], ['guard_name' => 'web']);
        $admin = Role::updateOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);


        $allPermissions = Permission::all();

        try {
            $root->syncPermissions($allPermissions);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ignore race condition 
        }

        $adminPermissions = Permission::whereIn('name', [
            'users.viewAny',
            'users.view',
            'users.create',
            'roles.viewAny',
            'roles.view',
            'invoices.viewAny',
            'invoices.view',
            'invoices.create',
        ])->get();

        try {
            $admin->syncPermissions($adminPermissions);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ignore race condition
        }
    }
}
