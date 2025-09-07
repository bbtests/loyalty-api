<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create regular users with varied data
        User::factory()->count(15)->create();

        // Create some users with specific characteristics for testing
        User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Mike Wilson',
            'email' => 'mike.wilson@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
    }
}
