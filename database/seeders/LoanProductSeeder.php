<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'code' => 'K01',
                'name' => 'Kredit Modal Kerja',
                'description' => 'Kredit untuk pembiayaan modal kerja usaha',
                'loan_type' => 'kmk',
                'interest_type' => 'flat',
                'min_amount' => 5000000,
                'max_amount' => 500000000,
                'interest_rate' => 12.00,
                'min_tenor_months' => 3,
                'max_tenor_months' => 36,
                'admin_fee_rate' => 1.00,
                'provision_fee_rate' => 0.50,
                'insurance_rate' => 0,
                'penalty_rate' => 0.50,
                'is_active' => true,
            ],
            [
                'code' => 'K02',
                'name' => 'Kredit Investasi',
                'description' => 'Kredit untuk pembiayaan investasi/aset tetap',
                'loan_type' => 'ki',
                'interest_type' => 'annuity',
                'min_amount' => 10000000,
                'max_amount' => 1000000000,
                'interest_rate' => 11.00,
                'min_tenor_months' => 12,
                'max_tenor_months' => 60,
                'admin_fee_rate' => 1.00,
                'provision_fee_rate' => 0.75,
                'insurance_rate' => 0.25,
                'penalty_rate' => 0.50,
                'is_active' => true,
            ],
            [
                'code' => 'K03',
                'name' => 'Kredit Konsumsi',
                'description' => 'Kredit untuk kebutuhan konsumsi',
                'loan_type' => 'kk',
                'interest_type' => 'effective',
                'min_amount' => 1000000,
                'max_amount' => 200000000,
                'interest_rate' => 14.00,
                'min_tenor_months' => 6,
                'max_tenor_months' => 48,
                'admin_fee_rate' => 1.50,
                'provision_fee_rate' => 0.50,
                'insurance_rate' => 0,
                'penalty_rate' => 1.00,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            LoanProduct::create($product);
        }
    }
}
