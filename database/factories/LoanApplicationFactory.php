<?php

namespace Database\Factories;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanApplication>
 */
class LoanApplicationFactory extends Factory
{
    protected $model = LoanApplication::class;

    public function definition(): array
    {
        return [
            'application_number' => 'APP'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'loan_product_id' => LoanProduct::factory(),
            'status' => LoanApplicationStatus::Submitted,
            'requested_amount' => fake()->randomFloat(2, 5000000, 100000000),
            'requested_tenor_months' => fake()->randomElement([6, 12, 24, 36, 48]),
            'interest_rate' => 12.00,
            'purpose' => fake()->sentence(),
        ];
    }
}
