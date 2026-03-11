<?php

namespace Database\Factories;

use App\Models\LoanAccount;
use App\Models\LoanSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanSchedule>
 */
class LoanScheduleFactory extends Factory
{
    public function definition(): array
    {
        $principal = fake()->randomFloat(2, 500000, 10000000);
        $interest = fake()->randomFloat(2, 50000, 1000000);

        return [
            'loan_account_id' => LoanAccount::factory(),
            'installment_number' => fake()->numberBetween(1, 12),
            'due_date' => fake()->dateTimeBetween('now', '+12 months'),
            'principal_amount' => $principal,
            'interest_amount' => $interest,
            'total_amount' => $principal + $interest,
            'outstanding_balance' => fake()->randomFloat(2, 10000000, 100000000),
            'principal_paid' => 0,
            'interest_paid' => 0,
            'penalty_paid' => 0,
            'is_paid' => false,
            'paid_date' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'is_paid' => true,
                'principal_paid' => $attributes['principal_amount'],
                'interest_paid' => $attributes['interest_amount'],
                'paid_date' => fake()->dateTimeBetween('-6 months', 'now'),
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'is_paid' => false,
            'paid_date' => null,
        ]);
    }
}
