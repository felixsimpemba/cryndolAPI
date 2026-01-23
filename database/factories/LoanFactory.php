<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Borrower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['PENDING', 'APPROVED', 'ACTIVE', 'PAID', 'DEFAULTED', 'CANCELLED'];

        return [
            'user_id' => User::factory(),
            'borrower_id' => Borrower::factory(),
            'principal' => fake()->numberBetween(5000, 50000),
            'interestRate' => fake()->randomFloat(2, 5, 25),
            'termMonths' => fake()->randomElement([6, 12, 18, 24, 36]),
            'startDate' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => fake()->randomElement($statuses),
            'totalPaid' => 0,
        ];
    }

    /**
     * Indicate that the loan is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Indicate that the loan is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'PENDING',
        ]);
    }

    /**
     * Indicate that the loan is paid.
     */
    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'PAID',
        ]);
    }
}
