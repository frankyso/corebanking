<?php

namespace Database\Seeders;

use App\Models\DepositProduct;
use Illuminate\Database\Seeder;

class DepositProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'code' => 'D01',
                'name' => 'Deposito Berjangka',
                'currency' => 'IDR',
                'min_amount' => 1000000,
                'max_amount' => 2000000000,
                'penalty_rate' => 0.50,
                'tax_rate' => 20,
                'tax_threshold' => 7500000,
                'is_active' => true,
                'rates' => [
                    ['tenor_months' => 1, 'min_amount' => 1000000, 'max_amount' => 49999999, 'interest_rate' => 3.50, 'is_active' => true],
                    ['tenor_months' => 1, 'min_amount' => 50000000, 'max_amount' => 499999999, 'interest_rate' => 3.75, 'is_active' => true],
                    ['tenor_months' => 1, 'min_amount' => 500000000, 'max_amount' => null, 'interest_rate' => 4.00, 'is_active' => true],
                    ['tenor_months' => 3, 'min_amount' => 1000000, 'max_amount' => 49999999, 'interest_rate' => 4.00, 'is_active' => true],
                    ['tenor_months' => 3, 'min_amount' => 50000000, 'max_amount' => 499999999, 'interest_rate' => 4.25, 'is_active' => true],
                    ['tenor_months' => 3, 'min_amount' => 500000000, 'max_amount' => null, 'interest_rate' => 4.50, 'is_active' => true],
                    ['tenor_months' => 6, 'min_amount' => 1000000, 'max_amount' => 49999999, 'interest_rate' => 4.50, 'is_active' => true],
                    ['tenor_months' => 6, 'min_amount' => 50000000, 'max_amount' => 499999999, 'interest_rate' => 4.75, 'is_active' => true],
                    ['tenor_months' => 6, 'min_amount' => 500000000, 'max_amount' => null, 'interest_rate' => 5.00, 'is_active' => true],
                    ['tenor_months' => 12, 'min_amount' => 1000000, 'max_amount' => 49999999, 'interest_rate' => 5.00, 'is_active' => true],
                    ['tenor_months' => 12, 'min_amount' => 50000000, 'max_amount' => 499999999, 'interest_rate' => 5.25, 'is_active' => true],
                    ['tenor_months' => 12, 'min_amount' => 500000000, 'max_amount' => null, 'interest_rate' => 5.50, 'is_active' => true],
                ],
            ],
            [
                'code' => 'D02',
                'name' => 'Deposito On Call',
                'currency' => 'IDR',
                'min_amount' => 50000000,
                'max_amount' => 2000000000,
                'penalty_rate' => 1.00,
                'tax_rate' => 20,
                'tax_threshold' => 7500000,
                'is_active' => true,
                'rates' => [
                    ['tenor_months' => 1, 'min_amount' => 50000000, 'max_amount' => 499999999, 'interest_rate' => 3.00, 'is_active' => true],
                    ['tenor_months' => 1, 'min_amount' => 500000000, 'max_amount' => null, 'interest_rate' => 3.25, 'is_active' => true],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $rates = $productData['rates'];
            unset($productData['rates']);

            $product = DepositProduct::create($productData);
            foreach ($rates as $rate) {
                $product->rates()->create($rate);
            }
        }
    }
}
