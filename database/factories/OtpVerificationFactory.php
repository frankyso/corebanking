<?php

namespace Database\Factories;

use App\Enums\OtpPurpose;
use App\Models\OtpVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpVerification>
 */
class OtpVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile_user_id' => null,
            'phone_number' => fake()->numerify('08##########'),
            'otp_hash' => bcrypt('123456'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinutes(1),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_used' => true,
        ]);
    }

    public function forTransaction(): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => OtpPurpose::Transaction,
        ]);
    }

    public function forPinReset(): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => OtpPurpose::PinReset,
        ]);
    }
}
