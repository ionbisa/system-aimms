<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ================= PERMISSIONS =================
        $permissions = [
            'view dashboard',

            'manage assets',
            'manage inventory',
            'manage stocks',
            'manage item requests',
            'approve item requests',
            'realize item requests',

            'approve purchase',
            'view reports',

            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================= ROLES =================
        $roles = [
            'Master Admin' => [
                'view dashboard',
                'manage assets',
                'manage inventory',
                'manage stocks',
                'manage item requests',
                'approve item requests',
                'realize item requests',
                'approve purchase',
                'view reports',
                'manage users',
            ],

            'Admin GA' => [
                'view dashboard',
                'manage assets',
                'manage inventory',
                'manage stocks',
                'manage item requests',
                'realize item requests',
            ],

            'Admin Produksi' => [
                'view dashboard',
                'manage inventory',
                'manage stocks',
                'manage item requests',
            ],

            'Kepala Produksi' => [
                'view dashboard',
                'approve item requests',
                'view reports',
            ],

            'Supervisor Operasional' => [
                'view dashboard',
                'approve purchase',
                'view reports',
            ],

            'SPV Operasional' => [
                'view dashboard',
                'manage item requests',
                'approve purchase',
                'view reports',
            ],

            'Manager Operasional' => [
                'view dashboard',
                'approve item requests',
                'approve purchase',
                'view reports',
            ],

            'Manager Finance' => [
                'view dashboard',
                'manage item requests',
                'approve purchase',
                'view reports',
            ],

            'Direktur Operasional' => [
                'view dashboard',
                'manage item requests',
                'approve purchase',
                'view reports',
            ],
        ];

        foreach ($roles as $roleName => $permissionList) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissionList);
        }
    }
}
