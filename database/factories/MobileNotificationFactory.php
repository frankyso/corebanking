<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\MobileNotification;
use App\Models\MobileUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileNotification>
 */
class MobileNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile_user_id' => MobileUser::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'type' => NotificationType::Transaction,
            'data' => null,
            'is_read' => false,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function promo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::Promo,
        ]);
    }

    public function security(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::Security,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::System,
        ]);
    }
}
