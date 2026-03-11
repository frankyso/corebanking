<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('now', '+1 year'),
            'name' => fake()->sentence(3),
            'type' => 'national',
        ];
    }

    public function regional(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'regional',
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }
}
