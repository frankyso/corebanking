<?php

namespace Database\Factories;

use App\Enums\InterestType;
use App\Enums\LoanType;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanProduct>
 */
class LoanProductFactory extends Factory
{
    protected $model = LoanProduct::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('L??')),
            'name' => 'Kredit '.$this->faker->word(),
            'loan_type' => $this->faker->randomElement(LoanType::cases()),
            'interest_type' => $this->faker->randomElement(InterestType::cases()),
            'min_amount' => 1000000,
            'max_amount' => 500000000,
            'interest_rate' => $this->faker->randomFloat(2, 10, 24),
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
            'admin_fee_rate' => 1.0,
            'provision_fee_rate' => 0.5,
            'insurance_rate' => 0,
            'penalty_rate' => 0.5,
            'is_active' => true,
        ];
    }
}
