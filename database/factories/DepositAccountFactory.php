<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositAccount>
 */
class DepositAccountFactory extends Factory
{
    protected $model = DepositAccount::class;

    public function definition(): array
    {
        $placementDate = fake()->dateTimeBetween('-6 months', 'now');
        $tenorMonths = fake()->randomElement([1, 3, 6, 12]);

        return [
            'account_number' => fake()->unique()->numerify('D01001#########'),
            'customer_id' => Customer::factory(),
            'deposit_product_id' => DepositProduct::factory(),
            'branch_id' => Branch::factory(),
            'status' => DepositStatus::Active,
            'principal_amount' => fake()->randomElement([5000000, 10000000, 25000000, 50000000, 100000000]),
            'interest_rate' => fake()->randomFloat(2, 3, 7),
            'tenor_months' => $tenorMonths,
            'interest_payment_method' => fake()->randomElement(InterestPaymentMethod::cases()),
            'rollover_type' => fake()->randomElement(RolloverType::cases()),
            'placement_date' => $placementDate,
            'maturity_date' => now()->parse($placementDate)->addMonths($tenorMonths),
            'accrued_interest' => 0,
            'total_interest_paid' => 0,
            'total_tax_paid' => 0,
            'is_pledged' => false,
            'created_by' => User::factory(),
        ];
    }
}
