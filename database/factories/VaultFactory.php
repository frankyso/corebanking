<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vault>
 */
class VaultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{2}'),
            'name' => 'Vault '.fake()->word(),
            'branch_id' => Branch::factory(),
            'balance' => fake()->randomFloat(2, 10000000, 500000000),
            'minimum_balance' => 5000000.00,
            'maximum_balance' => 1000000000.00,
            'is_active' => true,
            'custodian_id' => User::factory(),
        ];
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
