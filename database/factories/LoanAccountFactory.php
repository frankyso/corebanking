<?php

namespace Database\Factories;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanAccount>
 */
class LoanAccountFactory extends Factory
{
    protected $model = LoanAccount::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5000000, 100000000);

        return [
            'account_number' => 'L'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'loan_product_id' => LoanProduct::factory(),
            'status' => LoanStatus::Active,
            'principal_amount' => $amount,
            'interest_rate' => 12.00,
            'tenor_months' => 12,
            'outstanding_principal' => $amount,
            'outstanding_interest' => 0,
            'accrued_interest' => 0,
            'total_principal_paid' => 0,
            'total_interest_paid' => 0,
            'total_penalty_paid' => 0,
            'disbursement_date' => now()->subMonths(3),
            'maturity_date' => now()->addMonths(9),
            'dpd' => 0,
            'collectibility' => Collectibility::Current,
            'ckpn_amount' => 0,
        ];
    }
}
