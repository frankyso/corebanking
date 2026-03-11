<?php

namespace Database\Factories;

use App\Models\SystemParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemParameter>
 */
class SystemParameterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group' => fake()->randomElement(['general', 'loan', 'savings', 'deposit', 'accounting']),
            'key' => fake()->unique()->slug(2),
            'value' => (string) fake()->randomNumber(3),
            'type' => 'string',
            'description' => fake()->optional()->sentence(),
            'is_editable' => true,
        ];
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->randomElement(['true', 'false']),
        ]);
    }

    public function integer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 1000),
        ]);
    }

    public function decimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'decimal',
            'value' => (string) fake()->randomFloat(2, 0, 100),
        ]);
    }

    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_editable' => false,
        ]);
    }
}
