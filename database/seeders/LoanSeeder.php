<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
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

        foreach ($users as $user) {
            // Get user's customers
            $borrowers = Borrower::where('user_id', $user->id)->get();

            if ($borrowers->isEmpty()) {
                continue;
            }

            // Create loans for random customers
            foreach ($borrowers->random(min(3, $borrowers->count())) as $borrower) {
                // Create 1-3 loans per borrower
                foreach (range(1, rand(1, 3)) as $i) {
                    $loan = Loan::factory()->create([
                        'user_id' => $user->id,
                        'borrower_id' => $borrower->id,
                    ]);

                    // Add payments to some loans (60% chance)
                    if (rand(1, 100) <= 60) {
                        $paymentCount = rand(1, 5);

                        foreach (range(1, $paymentCount) as $j) {
                            LoanPayment::factory()->create([
                                'loan_id' => $loan->id,
                            ]);
                        }

                        // Update loan's totalPaid
                        $loan->totalPaid = $loan->payments()->sum('amountPaid');
                        $loan->save();
                    }
                }
            }
        }

        $this->command->info('Loans and payments seeded successfully!');
    }
}
