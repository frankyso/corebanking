<?php

namespace Database\Factories;

use App\Models\DepositProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositProduct>
 */
class DepositProductFactory extends Factory
{
    protected $model = DepositProduct::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('D??')),
            'name' => 'Deposito '.fake()->word(),
            'currency' => 'IDR',
            'min_amount' => 1000000,
            'max_amount' => 2000000000,
            'penalty_rate' => 0.5,
            'tax_rate' => 20,
            'tax_threshold' => 7500000,
            'is_active' => true,
        ];
    }
}
