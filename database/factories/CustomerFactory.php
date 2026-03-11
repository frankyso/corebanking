<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cif_number' => fake()->unique()->numerify('001##0000##'),
            'customer_type' => fake()->randomElement(CustomerType::cases()),
            'status' => CustomerStatus::Active,
            'risk_rating' => RiskRating::Low,
            'branch_id' => Branch::query()->inRandomOrder()->value('id') ?? 1,
            'approval_status' => ApprovalStatus::Approved,
            'created_by' => User::query()->inRandomOrder()->value('id') ?? 1,
            'approved_by' => User::query()->inRandomOrder()->value('id') ?? 1,
            'approved_at' => now(),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => CustomerType::Individual,
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => CustomerType::Corporate,
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::PendingApproval,
            'approval_status' => ApprovalStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_rating' => RiskRating::High,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::Blocked,
        ]);
    }
}
