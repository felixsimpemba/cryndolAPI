<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Borrower;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Create customers for each user
        foreach ($users as $user) {
            Borrower::factory()->count(rand(3, 8))->create([
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Customers seeded successfully!');
    }
}
