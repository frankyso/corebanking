<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MobileUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileUser>
 */
class MobileUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'phone_number' => fake()->unique()->numerify('08##########'),
            'pin_hash' => bcrypt('123456'),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function pinLocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pin_attempts' => 5,
            'pin_locked_until' => now()->addMinutes(30),
        ]);
    }
}
