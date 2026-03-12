<?php

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\DepositAccount;
use App\Models\DepositInterestAccrual;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\DepositTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
});

// ============================================================================
// DepositAccount - Additional Scope and Business Logic
// ============================================================================
describe('DepositAccount additional coverage', function (): void {
    it('scope maturing returns only active accounts with past maturity', function (): void {
        DepositAccount::factory()->create([
            'status' => DepositStatus::Active,
            'maturity_date' => now()->subDays(5),
        ]);
        DepositAccount::factory()->create([
            'status' => DepositStatus::Closed,
            'maturity_date' => now()->subDays(5),
        ]);

        $maturing = DepositAccount::maturing(now())->get();

        expect($maturing)->toHaveCount(1)
            ->and($maturing->first()->status)->toBe(DepositStatus::Active);
    });

    it('isMatured returns true for today maturity date', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->startOfDay(),
        ]);

        expect($account->isMatured())->toBeTrue();
    });

    it('daysToMaturity returns positive value for future dates', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->addDays(90),
        ]);

        expect($account->daysToMaturity())->toBeGreaterThanOrEqual(89)
            ->and($account->daysToMaturity())->toBeLessThanOrEqual(90);
    });

    it('casts all enum fields correctly', function (): void {
        $account = DepositAccount::factory()->create([
            'status' => DepositStatus::Matured,
            'interest_payment_method' => InterestPaymentMethod::Maturity,
            'rollover_type' => RolloverType::None,
        ]);

        expect($account->status)->toBe(DepositStatus::Matured)
            ->and($account->interest_payment_method)->toBe(InterestPaymentMethod::Maturity)
            ->and($account->rollover_type)->toBe(RolloverType::None);
    });

    it('casts decimal fields properly', function (): void {
        $account = DepositAccount::factory()->create([
            'principal_amount' => 100000000.00,
            'interest_rate' => 6.50000,
            'accrued_interest' => 534246.58,
            'total_interest_paid' => 1000000.00,
            'total_tax_paid' => 200000.00,
        ]);

        expect($account->principal_amount)->toBe('100000000.00')
            ->and($account->interest_rate)->toBe('6.50000')
            ->and($account->accrued_interest)->toBe('534246.58')
            ->and($account->total_interest_paid)->toBe('1000000.00')
            ->and($account->total_tax_paid)->toBe('200000.00');
    });

    it('savingsAccount relationship links to linked savings', function (): void {
        $account = DepositAccount::factory()->create();

        expect($account->savingsAccount())->toBeInstanceOf(BelongsTo::class);
    });

    it('interestAccruals returns related accruals', function (): void {
        $account = DepositAccount::factory()->create();
        DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => now(),
            'principal' => 50000000,
            'interest_rate' => 5.50,
            'accrued_amount' => 7534.25,
            'tax_amount' => 1506.85,
            'is_posted' => false,
        ]);

        expect($account->interestAccruals)->toHaveCount(1)
            ->and($account->interestAccruals->first())->toBeInstanceOf(DepositInterestAccrual::class);
    });
});

// ============================================================================
// DepositProduct - Additional Coverage
// ============================================================================
describe('DepositProduct additional coverage', function (): void {
    it('casts decimal fee fields correctly', function (): void {
        $product = DepositProduct::factory()->create([
            'min_amount' => 1000000.00,
            'max_amount' => 500000000.00,
            'penalty_rate' => 1.50000,
            'tax_rate' => 20.00000,
            'tax_threshold' => 7500000.00,
        ]);

        expect($product->min_amount)->toBe('1000000.00')
            ->and($product->max_amount)->toBe('500000000.00')
            ->and($product->penalty_rate)->toBe('1.50000')
            ->and($product->tax_rate)->toBe('20.00000')
            ->and($product->tax_threshold)->toBe('7500000.00');
    });

    it('getRateForTenorAndAmount handles null max_amount', function (): void {
        $product = DepositProduct::factory()->create();
        DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 12,
            'min_amount' => 1000000,
            'max_amount' => null,
            'interest_rate' => 7.00,
            'is_active' => true,
        ]);

        $rate = $product->getRateForTenorAndAmount(12, 999999999);

        expect($rate)->toBeInstanceOf(DepositProductRate::class)
            ->and($rate->interest_rate)->toBe('7.00000');
    });

    it('getRateForTenorAndAmount ignores inactive rates', function (): void {
        $product = DepositProduct::factory()->create();
        DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 6,
            'min_amount' => 1000000,
            'max_amount' => 100000000,
            'interest_rate' => 5.00,
            'is_active' => false,
        ]);

        $rate = $product->getRateForTenorAndAmount(6, 50000000);

        expect($rate)->toBeNull();
    });

    it('glInterestExpense returns BelongsTo relationship', function (): void {
        $product = DepositProduct::factory()->create();

        expect($product->glDeposit())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glInterestExpense())->toBeInstanceOf(BelongsTo::class);
    });

    it('accounts relationship returns deposit accounts', function (): void {
        $product = DepositProduct::factory()->create();
        DepositAccount::factory()->create(['deposit_product_id' => $product->id]);

        expect($product->accounts())->toBeInstanceOf(HasMany::class)
            ->and($product->accounts)->toHaveCount(1);
    });
});

// ============================================================================
// DepositProductRate - Additional Coverage
// ============================================================================
describe('DepositProductRate additional coverage', function (): void {
    it('casts decimal fields correctly', function (): void {
        $product = DepositProduct::factory()->create();

        $rate = DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 6,
            'min_amount' => 5000000.00,
            'max_amount' => 100000000.00,
            'interest_rate' => 5.75000,
            'is_active' => true,
        ]);

        expect($rate->min_amount)->toBe('5000000.00')
            ->and($rate->max_amount)->toBe('100000000.00')
            ->and($rate->interest_rate)->toBe('5.75000')
            ->and($rate->is_active)->toBeTrue()->toBeBool();
    });
});

// ============================================================================
// DepositTransaction - Additional Coverage
// ============================================================================
describe('DepositTransaction additional coverage', function (): void {
    it('casts amount as decimal and transaction_date as date', function (): void {
        $account = DepositAccount::factory()->create();

        $txn = DepositTransaction::create([
            'reference_number' => 'DTXN-CAST-001',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'placement',
            'amount' => 25000000.50,
            'description' => 'Test placement',
            'transaction_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);

        expect($txn->amount)->toBe('25000000.50')
            ->and($txn->transaction_date)->toBeInstanceOf(Carbon::class)
            ->and($txn->transaction_date->toDateString())->toBe('2026-03-10');
    });

    it('performer relationship returns the performing user', function (): void {
        $account = DepositAccount::factory()->create();

        $txn = DepositTransaction::create([
            'reference_number' => 'DTXN-PERF-001',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'interest',
            'amount' => 500000,
            'transaction_date' => now(),
            'performed_by' => $this->user->id,
        ]);

        expect($txn->performer->id)->toBe($this->user->id);
    });
});

// ============================================================================
// DepositInterestAccrual - Additional Coverage
// ============================================================================
describe('DepositInterestAccrual additional coverage', function (): void {
    it('casts all decimal fields correctly', function (): void {
        $account = DepositAccount::factory()->create();

        $accrual = DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'principal' => 100000000.00,
            'interest_rate' => 6.25000,
            'accrued_amount' => 17123.29,
            'tax_amount' => 3424.66,
            'is_posted' => true,
            'posted_at' => '2026-03-10',
        ]);

        expect($accrual->principal)->toBe('100000000.00')
            ->and($accrual->interest_rate)->toBe('6.25000')
            ->and($accrual->accrued_amount)->toBe('17123.29')
            ->and($accrual->tax_amount)->toBe('3424.66')
            ->and($accrual->posted_at)->toBeInstanceOf(Carbon::class);
    });
});
