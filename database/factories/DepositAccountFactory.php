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
        $placementDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $tenorMonths = $this->faker->randomElement([1, 3, 6, 12]);

        return [
            'account_number' => $this->faker->unique()->numerify('D01001#########'),
            'customer_id' => Customer::factory(),
            'deposit_product_id' => DepositProduct::factory(),
            'branch_id' => Branch::factory(),
            'status' => DepositStatus::Active,
            'principal_amount' => $this->faker->randomElement([5000000, 10000000, 25000000, 50000000, 100000000]),
            'interest_rate' => $this->faker->randomFloat(2, 3, 7),
            'tenor_months' => $tenorMonths,
            'interest_payment_method' => $this->faker->randomElement(InterestPaymentMethod::cases()),
            'rollover_type' => $this->faker->randomElement(RolloverType::cases()),
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
