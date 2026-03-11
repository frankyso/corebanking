<?php

namespace Database\Seeders;

use App\Enums\InterestCalcMethod;
use App\Models\ChartOfAccount;
use App\Models\SavingsProduct;
use Illuminate\Database\Seeder;

class SavingsProductSeeder extends Seeder
{
    public function run(): void
    {
        $glSavingsUmum = ChartOfAccount::where('account_code', '2.01.01.000')->value('id');
        $glSavingsPelajar = ChartOfAccount::where('account_code', '2.01.02.000')->value('id');
        $glInterestExpense = ChartOfAccount::where('account_code', '5.01.00.000')->value('id');

        $products = [
            [
                'code' => 'T01',
                'name' => 'Tabungan Umum',
                'description' => 'Tabungan untuk masyarakat umum dengan bunga kompetitif',
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
                'interest_rate' => 2.50000,
                'min_opening_balance' => 50_000,
                'min_balance' => 25_000,
                'admin_fee_monthly' => 5_000,
                'closing_fee' => 25_000,
                'dormant_fee' => 5_000,
                'dormant_period_days' => 365,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'gl_savings_id' => $glSavingsUmum,
                'gl_interest_expense_id' => $glInterestExpense,
            ],
            [
                'code' => 'T02',
                'name' => 'Tabungan Pelajar',
                'description' => 'Tabungan khusus pelajar tanpa biaya admin',
                'interest_calc_method' => InterestCalcMethod::LowestBalance,
                'interest_rate' => 1.50000,
                'min_opening_balance' => 10_000,
                'min_balance' => 10_000,
                'admin_fee_monthly' => 0,
                'closing_fee' => 10_000,
                'dormant_fee' => 0,
                'dormant_period_days' => 365,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'gl_savings_id' => $glSavingsPelajar,
                'gl_interest_expense_id' => $glInterestExpense,
            ],
            [
                'code' => 'T03',
                'name' => 'Tabungan Bisnis',
                'description' => 'Tabungan untuk nasabah korporasi dan UMKM',
                'interest_calc_method' => InterestCalcMethod::AverageBalance,
                'interest_rate' => 3.00000,
                'min_opening_balance' => 500_000,
                'min_balance' => 100_000,
                'max_balance' => null,
                'admin_fee_monthly' => 10_000,
                'closing_fee' => 50_000,
                'dormant_fee' => 10_000,
                'dormant_period_days' => 180,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'gl_savings_id' => $glSavingsUmum,
                'gl_interest_expense_id' => $glInterestExpense,
            ],
        ];

        foreach ($products as $product) {
            SavingsProduct::create($product);
        }
    }
}
