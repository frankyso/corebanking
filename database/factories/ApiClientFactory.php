<?php

namespace Database\Factories;

use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'client_id' => 'test-'.fake()->unique()->slug(2),
            'secret_key' => Str::random(64),
            'is_active' => true,
            'rate_limit' => 60,
            'allowed_ips' => null,
            'permissions' => null,
        ];
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the client has IP restrictions.
     *
     * @param  array<int, string>  $ips
     */
    public function withIpRestriction(array $ips = ['127.0.0.1']): static
    {
        return $this->state(fn (array $attributes): array => [
            'allowed_ips' => $ips,
        ]);
    }
}
