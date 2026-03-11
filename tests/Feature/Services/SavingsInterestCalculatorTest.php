<?php

use App\Enums\InterestCalcMethod;
use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Services\SavingsInterestCalculator;
use Carbon\Carbon;

describe('SavingsInterestCalculator', function () {
    beforeEach(function () {
        $this->calculator = app(SavingsInterestCalculator::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
    });

    describe('calculateDailyAccrual', function () {
        it('creates an accrual record with correct amounts for a non-leap year', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000001',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000,
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $date = Carbon::create(2025, 3, 15); // 2025 is not a leap year
            $accrual = $this->calculator->calculateDailyAccrual($account, $date);

            expect($accrual)->toBeInstanceOf(SavingsInterestAccrual::class)
                ->and($accrual->savings_account_id)->toBe($account->id)
                ->and($accrual->accrual_date->toDateString())->toBe('2025-03-15')
                ->and((float) $accrual->balance)->toBe(10_000_000.00)
                ->and((float) $accrual->interest_rate)->toBeGreaterThan(0);

            // Expected: (10,000,000 / 365) * (3 / 100) = 821.91 approx
            $expectedDaily = bcmul(
                bcdiv('10000000', '365', 10),
                bcdiv('3', '100', 10),
                2
            );
            expect((float) $accrual->accrued_amount)->toBe((float) $expectedDaily);
        });

        it('handles leap year with 366 days', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000002',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000,
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $leapDate = Carbon::create(2024, 2, 29); // 2024 is a leap year
            $accrual = $this->calculator->calculateDailyAccrual($account, $leapDate);

            $expectedDaily = bcmul(
                bcdiv('10000000', '366', 10),
                bcdiv('3', '100', 10),
                2
            );

            expect((float) $accrual->accrued_amount)->toBe((float) $expectedDaily);
        });

        it('applies tax when balance is at or above threshold', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000003',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000, // Above threshold of 7.5M
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $date = Carbon::create(2025, 6, 15);
            $accrual = $this->calculator->calculateDailyAccrual($account, $date);

            expect((float) $accrual->tax_amount)->toBeGreaterThan(0);
        });

        it('does not apply tax when balance is below threshold', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000004',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 5_000_000, // Below threshold of 7.5M
                'hold_amount' => 0,
                'available_balance' => 5_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $date = Carbon::create(2025, 6, 15);
            $accrual = $this->calculator->calculateDailyAccrual($account, $date);

            expect((float) $accrual->tax_amount)->toBe(0.00);
        });
    });

    describe('calculateMonthlyInterest', function () {
        it('uses DailyBalance method based on accrual records', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000005',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000,
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            // Create daily accruals for January 2025 with varying balances
            $month = Carbon::create(2025, 1, 1);
            for ($day = 1; $day <= 31; $day++) {
                $balance = $day <= 15 ? 10_000_000 : 15_000_000;
                $account->interestAccruals()->create([
                    'accrual_date' => Carbon::create(2025, 1, $day),
                    'balance' => $balance,
                    'interest_rate' => 3.0,
                    'accrued_amount' => 0,
                    'tax_amount' => 0,
                ]);
            }

            $result = $this->calculator->calculateMonthlyInterest($account, $month);

            expect($result)->toHaveKeys(['balance', 'interest', 'tax', 'net_interest'])
                ->and((float) $result['interest'])->toBeGreaterThan(0)
                ->and((float) $result['tax'])->toBeGreaterThan(0);

            // Average balance should be between 10M and 15M
            $avgBalance = (float) $result['balance'];
            expect($avgBalance)->toBeGreaterThan(10_000_000)
                ->and($avgBalance)->toBeLessThan(15_000_000);
        });

        it('uses LowestBalance method returning minimum balance from accruals', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::LowestBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000006',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 15_000_000,
                'hold_amount' => 0,
                'available_balance' => 15_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            // Accruals with varying balances
            $month = Carbon::create(2025, 1, 1);
            $balances = [5_000_000, 10_000_000, 15_000_000, 8_000_000, 12_000_000];
            foreach ($balances as $index => $balance) {
                $account->interestAccruals()->create([
                    'accrual_date' => Carbon::create(2025, 1, $index + 1),
                    'balance' => $balance,
                    'interest_rate' => 3.0,
                    'accrued_amount' => 0,
                    'tax_amount' => 0,
                ]);
            }

            $result = $this->calculator->calculateMonthlyInterest($account, $month);

            // Lowest balance should be 5,000,000
            expect((float) $result['balance'])->toBe(5_000_000.00);
        });

        it('uses account balance when no accrual records exist', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000007',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000,
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $month = Carbon::create(2025, 1, 1);
            $result = $this->calculator->calculateMonthlyInterest($account, $month);

            expect((float) $result['balance'])->toBe(10_000_000.00);
        });

        it('calculates net_interest as interest minus tax', function () {
            $product = SavingsProduct::factory()->create([
                'interest_rate' => 3.00000,
                'tax_rate' => 20.00000,
                'tax_threshold' => 7_500_000,
                'interest_calc_method' => InterestCalcMethod::DailyBalance,
            ]);

            $account = SavingsAccount::create([
                'account_number' => 'T01001000000008',
                'customer_id' => $this->customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10_000_000,
                'hold_amount' => 0,
                'available_balance' => 10_000_000,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $month = Carbon::create(2025, 1, 1);
            $result = $this->calculator->calculateMonthlyInterest($account, $month);

            $expectedNet = bcsub($result['interest'], $result['tax'], 2);
            expect($result['net_interest'])->toBe($expectedNet);
        });
    });
});
