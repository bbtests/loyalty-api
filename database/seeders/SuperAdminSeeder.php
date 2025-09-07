<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('constants.super_admin_email')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(config('constants.super_admin_password')),
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }
    }
}
