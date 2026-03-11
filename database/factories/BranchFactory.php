<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}'),
            'name' => 'Cabang '.fake()->city(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->numerify('#####'),
            'phone' => fake()->phoneNumber(),
            'is_head_office' => false,
            'is_active' => true,
            'head_id' => User::factory(),
        ];
    }

    public function headOffice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_head_office' => true,
            'name' => 'Kantor Pusat',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
