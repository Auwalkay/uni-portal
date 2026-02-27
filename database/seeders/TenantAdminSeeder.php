<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = tenant();
        $domain = $tenant ? $tenant->id.'.localhost' : 'localhost';

        $adminEmail = $tenant->admin_email ?? 'admin@'.$domain;
        $adminName = $tenant->admin_name ?? 'University Admin';
        $adminPassword = $tenant->admin_password_hash ?? Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => $adminPassword,
                'email_verified_at' => now(),
            ]
        );

        // Ensure Spatie reads from the newly created tenant DB
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $admin->assignRole('admin');
    }
}
