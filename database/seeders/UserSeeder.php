<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role ada
        $roles = [
            'Master Admin',
            'Admin GA',
            'Admin Produksi',
            'Kepala Produksi',
            'SPV Operasional',
            'Supervisor Operasional',
            'Manager Operasional',
            'Manager Finance',
            'Direktur Operasional',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $users = [
            ['name'=>'Master Admin','email'=>'master@aimms.local','role'=>'Master Admin'],
            ['name'=>'Admin GA','email'=>'ga@aimms.local','role'=>'Admin GA'],
            ['name'=>'Admin Produksi','email'=>'adm-produksi@aimms.local','role'=>'Admin Produksi'],
            ['name'=>'Agus Kepala Produksi','email'=>'agus.kapro@aimms.local','role'=>'Kepala Produksi'],
            ['name'=>'Nanta Kepala Produksi','email'=>'nanta.kapro@aimms.local','role'=>'Kepala Produksi'],
            ['name'=>'Dul Kepala Produksi','email'=>'dul.kapro@aimms.local','role'=>'Kepala Produksi'],
            ['name'=>'SPV Operasional','email'=>'acep.spv@aimms.local','role'=>'SPV Operasional'],
            ['name'=>'Manager Operasional','email'=>'helmi.mo@aimms.local','role'=>'Manager Operasional'],
            ['name'=>'Manager Finance','email'=>'jeje.mf@aimms.local','role'=>'Manager Finance'],
            ['name'=>'Direktur Operasional','email'=>'jaja.dirut@aimms.local','role'=>'Direktur Operasional'],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password123'),
                ]
            );

            $user->syncRoles([$u['role']]);
        }
    }
}
