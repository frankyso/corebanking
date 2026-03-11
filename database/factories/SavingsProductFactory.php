<?php

namespace Database\Factories;

use App\Enums\InterestCalcMethod;
use App\Models\SavingsProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsProduct>
 */
class SavingsProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('T##'),
            'name' => 'Tabungan '.fake()->word(),
            'interest_calc_method' => fake()->randomElement(InterestCalcMethod::cases()),
            'interest_rate' => fake()->randomFloat(2, 1, 5),
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'admin_fee_monthly' => 5000,
            'closing_fee' => 25000,
            'is_active' => true,
        ];
    }
}
