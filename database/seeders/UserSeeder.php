<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin/test user
        $admin = User::create([
            'fullName' => 'Admin User',
            'email' => 'admin@cryndol.com',
            'phoneNumber' => '+1234567890',
            'password' => 'password',
            'is_super_user' => true,
        ]);

        // Create business profile for admin
        BusinessProfile::create([
            'user_id' => $admin->id,
            'businessName' => 'Cryndol Financial Services',
        ]);

        // Create test users
        $testUser = User::create([
            'fullName' => 'John Doe',
            'email' => 'test@example.com',
            'phoneNumber' => '+1987654321',
            'password' => 'password',
        ]);

        BusinessProfile::create([
            'user_id' => $testUser->id,
            'businessName' => 'John\'s Consulting LLC',
        ]);

        // Create additional random users with business profiles
        // Create additional random users with business profiles
        // User::factory()
        //     ->count(5)
        //     ->has(BusinessProfile::factory())
        //     ->create();

        $this->command->info('Users and business profiles seeded successfully!');
    }
}

