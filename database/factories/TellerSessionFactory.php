<?php

namespace Database\Factories;

use App\Enums\TellerSessionStatus;
use App\Models\Branch;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TellerSession>
 */
class TellerSessionFactory extends Factory
{
    public function definition(): array
    {
        $openingBalance = fake()->randomFloat(2, 1000000, 50000000);

        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'vault_id' => Vault::factory(),
            'status' => TellerSessionStatus::Open,
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'closing_balance' => null,
            'total_cash_in' => 0,
            'total_cash_out' => 0,
            'transaction_count' => 0,
            'opened_at' => now(),
            'closed_at' => null,
            'closing_notes' => null,
        ];
    }

    public function closed(): static
    {
        $openingBalance = fake()->randomFloat(2, 1000000, 50000000);
        $cashIn = fake()->randomFloat(2, 500000, 20000000);
        $cashOut = fake()->randomFloat(2, 500000, 15000000);
        $closingBalance = $openingBalance + $cashIn - $cashOut;

        return $this->state(fn (array $attributes): array => [
            'status' => TellerSessionStatus::Closed,
            'opening_balance' => $openingBalance,
            'current_balance' => $closingBalance,
            'closing_balance' => $closingBalance,
            'total_cash_in' => $cashIn,
            'total_cash_out' => $cashOut,
            'transaction_count' => fake()->numberBetween(5, 50),
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
            'closing_notes' => fake()->optional()->sentence(),
        ]);
    }
}
