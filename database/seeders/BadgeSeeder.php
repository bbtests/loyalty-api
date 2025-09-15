<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('badges')->insert([
            [
                'name' => 'Bronze Member',
                'description' => 'Welcome to our loyalty program',
                'requirements' => json_encode(['points_minimum' => 100]),
                'icon' => 'bronze-medal',
                'tier' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver Member',
                'description' => 'Reach 2500 points',
                'requirements' => json_encode(['points_minimum' => 2500]),
                'icon' => 'silver-medal',
                'tier' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Member',
                'description' => 'Reach 10000 points',
                'requirements' => json_encode(['points_minimum' => 10000]),
                'icon' => 'gold-medal',
                'tier' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum Member',
                'description' => 'Reach 25000 points and make 50+ purchases',
                'requirements' => json_encode(['points_minimum' => 25000, 'purchases_minimum' => 50]),
                'icon' => 'platinum-medal',
                'tier' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
