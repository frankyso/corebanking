<?php

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositInterestAccrual;
use App\Models\DepositProduct;
use App\Models\DepositTransaction;
use App\Models\User;
use App\Services\DepositAccrualService;
use Carbon\Carbon;

describe('DepositAccrualService', function (): void {
    beforeEach(function (): void {
        $this->service = app(DepositAccrualService::class);

        $this->branch = Branch::factory()->create();
        $this->performer = User::factory()->create();

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->performer->id,
            'approved_by' => $this->performer->id,
        ]);
    });

    /**
     * Helper to create a DepositAccount with proper dependencies.
     *
     * @param  array<string, mixed>  $attributes
     */
    function createDepositAccount(array $attributes = []): DepositAccount
    {
        return DepositAccount::factory()->create(array_merge([
            'customer_id' => test()->customer->id,
            'branch_id' => test()->branch->id,
            'created_by' => test()->performer->id,
        ], $attributes));
    }

    describe('accrueDaily', function (): void {
        it('creates accrual record for active account', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'principal_amount' => 100000000,
                'interest_rate' => 5.00,
                'accrued_interest' => 0,
            ]);

            $date = Carbon::create(2026, 3, 15);
            $this->service->accrueDaily($account, $date);

            expect(DepositInterestAccrual::count())->toBe(1);

            $accrual = DepositInterestAccrual::first();
            expect($accrual->deposit_account_id)->toBe($account->id)
                ->and($accrual->accrual_date->format('Y-m-d'))->toBe('2026-03-15')
                ->and((float) $accrual->principal)->toBe(100000000.0)
                ->and((float) $accrual->interest_rate)->toBe(5.0)
                ->and($accrual->is_posted)->toBeFalse();
        });

        it('updates accrued_interest on account', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 0]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'principal_amount' => 100000000,
                'interest_rate' => 7.30,
                'accrued_interest' => 0,
            ]);

            $date = Carbon::create(2026, 3, 15); // 2026 is not a leap year, 365 days
            $this->service->accrueDaily($account, $date);

            $account->refresh();
            $expectedDaily = (float) bcmul(
                '100000000',
                bcdiv('7.30', (string) (365 * 100), 10),
                2,
            );

            expect((float) $account->accrued_interest)->toBe($expectedDaily);
        });

        it('skips non-active accounts', function (): void {
            $product = DepositProduct::factory()->create();
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Matured,
                'principal_amount' => 100000000,
                'interest_rate' => 5.00,
                'accrued_interest' => 0,
            ]);

            $date = Carbon::create(2026, 3, 15);
            $this->service->accrueDaily($account, $date);

            expect(DepositInterestAccrual::count())->toBe(0)
                ->and((float) $account->fresh()->accrued_interest)->toBe(0.0);
        });

        it('uses 366 days for leap year', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 0]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'principal_amount' => 100000000,
                'interest_rate' => 3.66,
                'accrued_interest' => 0,
            ]);

            $leapDate = Carbon::create(2024, 2, 29); // 2024 is a leap year
            $this->service->accrueDaily($account, $leapDate);

            $account->refresh();
            $expectedDaily = (float) bcmul(
                '100000000',
                bcdiv('3.66', (string) (366 * 100), 10),
                2,
            );

            expect((float) $account->accrued_interest)->toBe($expectedDaily);
        });

        it('uses 365 days for non-leap year', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 0]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'principal_amount' => 100000000,
                'interest_rate' => 3.65,
                'accrued_interest' => 0,
            ]);

            $nonLeapDate = Carbon::create(2026, 6, 15); // 2026 is not a leap year
            $this->service->accrueDaily($account, $nonLeapDate);

            $account->refresh();
            $expectedDaily = (float) bcmul(
                '100000000',
                bcdiv('3.65', (string) (365 * 100), 10),
                2,
            );

            expect((float) $account->accrued_interest)->toBe($expectedDaily);
        });
    });

    describe('payMonthlyInterest', function (): void {
        it('creates interest_payment transaction', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 500000,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            $interestTx = DepositTransaction::where('deposit_account_id', $account->id)
                ->where('transaction_type', 'interest_payment')
                ->first();

            // Net interest = 500000 - (500000 * 20/100) = 500000 - 100000 = 400000
            expect($interestTx)->not->toBeNull()
                ->and((float) $interestTx->amount)->toBe(400000.0)
                ->and($interestTx->performed_by)->toBe($this->performer->id);
        });

        it('creates tax transaction when tax is greater than zero', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 500000,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            $taxTx = DepositTransaction::where('deposit_account_id', $account->id)
                ->where('transaction_type', 'tax')
                ->first();

            expect($taxTx)->not->toBeNull()
                ->and((float) $taxTx->amount)->toBe(100000.0);
        });

        it('resets accrued_interest to zero', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 500000,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            $account->refresh();
            expect((float) $account->accrued_interest)->toBe(0.0)
                ->and((float) $account->total_interest_paid)->toBe(400000.0)
                ->and((float) $account->total_tax_paid)->toBe(100000.0);
        });

        it('marks accruals as posted', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 0]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 100000,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
            ]);

            DepositInterestAccrual::create([
                'deposit_account_id' => $account->id,
                'accrual_date' => now()->subDays(5),
                'principal' => $account->principal_amount,
                'interest_rate' => $account->interest_rate,
                'accrued_amount' => 50000,
                'tax_amount' => 0,
                'is_posted' => false,
            ]);

            DepositInterestAccrual::create([
                'deposit_account_id' => $account->id,
                'accrual_date' => now()->subDays(4),
                'principal' => $account->principal_amount,
                'interest_rate' => $account->interest_rate,
                'accrued_amount' => 50000,
                'tax_amount' => 0,
                'is_posted' => false,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            $unposted = DepositInterestAccrual::where('deposit_account_id', $account->id)
                ->where('is_posted', false)
                ->count();

            expect($unposted)->toBe(0);

            $posted = DepositInterestAccrual::where('deposit_account_id', $account->id)
                ->where('is_posted', true)
                ->count();

            expect($posted)->toBe(2);
        });

        it('skips non-monthly payment method', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Maturity,
                'accrued_interest' => 500000,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            expect(DepositTransaction::count())->toBe(0);
        });

        it('skips non-active accounts', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Closed,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 500000,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            expect(DepositTransaction::count())->toBe(0);
        });

        it('skips when accrued_interest is zero', function (): void {
            $product = DepositProduct::factory()->create(['tax_rate' => 20]);
            $account = createDepositAccount([
                'deposit_product_id' => $product->id,
                'status' => DepositStatus::Active,
                'interest_payment_method' => InterestPaymentMethod::Monthly,
                'accrued_interest' => 0,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
            ]);

            $this->service->payMonthlyInterest($account, $this->performer);

            expect(DepositTransaction::count())->toBe(0)
                ->and((float) $account->fresh()->accrued_interest)->toBe(0.0);
        });
    });
});
