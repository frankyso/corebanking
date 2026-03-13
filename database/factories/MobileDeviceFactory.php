<?php

namespace Database\Factories;

use App\Enums\DevicePlatform;
use App\Models\MobileDevice;
use App\Models\MobileUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileDevice>
 */
class MobileDeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile_user_id' => MobileUser::factory(),
            'device_id' => fake()->uuid(),
            'device_name' => fake()->randomElement(['Samsung Galaxy S24', 'iPhone 15', 'Xiaomi Redmi Note 12']),
            'platform' => fake()->randomElement(DevicePlatform::cases()),
            'fcm_token' => null,
            'is_active' => true,
            'last_used_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withFcmToken(): static
    {
        return $this->state(fn (array $attributes): array => [
            'fcm_token' => fake()->sha256(),
        ]);
    }
}
