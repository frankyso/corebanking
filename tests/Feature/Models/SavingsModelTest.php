<?php

use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use App\Models\SavingsProduct;
use App\Models\SavingsTransaction;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
});

// ============================================================================
// SavingsAccount - Scope & Business Logic Coverage
// ============================================================================
describe('SavingsAccount scopes and business logic', function (): void {
    it('scope dormant filters dormant accounts', function (): void {
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Dormant]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Active]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Frozen]);

        $dormant = SavingsAccount::byStatus(SavingsAccountStatus::Dormant)->get();

        expect($dormant)->toHaveCount(1)
            ->and($dormant->first()->status)->toBe(SavingsAccountStatus::Dormant);
    });

    it('scope byStatus filters frozen accounts', function (): void {
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Frozen]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Active]);

        $frozen = SavingsAccount::byStatus(SavingsAccountStatus::Frozen)->get();

        expect($frozen)->toHaveCount(1)
            ->and($frozen->first()->status)->toBe(SavingsAccountStatus::Frozen);
    });

    it('scope active excludes closed and frozen accounts', function (): void {
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Active]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Closed]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Frozen]);

        $active = SavingsAccount::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->every(fn ($a): bool => $a->status === SavingsAccountStatus::Active))->toBeTrue();
    });

    it('recalculateAvailableBalance handles zero hold amount', function (): void {
        $account = SavingsAccount::factory()->create([
            'balance' => 5000000.00,
            'hold_amount' => 0.00,
            'available_balance' => 0.00,
        ]);

        $account->recalculateAvailableBalance();
        $account->refresh();

        expect($account->available_balance)->toBe('5000000.00');
    });

    it('recalculateAvailableBalance handles hold exceeding balance', function (): void {
        $account = SavingsAccount::factory()->create([
            'balance' => 100000.00,
            'hold_amount' => 500000.00,
            'available_balance' => 100000.00,
        ]);

        $account->recalculateAvailableBalance();
        $account->refresh();

        expect($account->available_balance)->toBe('-400000.00');
    });

    it('casts decimal fields as string with two decimals', function (): void {
        $account = SavingsAccount::factory()->create([
            'balance' => 1234567.89,
            'hold_amount' => 50000.50,
            'accrued_interest' => 12345.67,
        ]);

        expect($account->balance)->toBe('1234567.89')
            ->and($account->hold_amount)->toBe('50000.50')
            ->and($account->accrued_interest)->toBe('12345.67');
    });

    it('casts date fields correctly', function (): void {
        $account = SavingsAccount::factory()->create([
            'opened_at' => '2026-01-15',
            'last_transaction_at' => '2026-03-10',
        ]);

        expect($account->opened_at)->toBeInstanceOf(Carbon::class)
            ->and($account->last_transaction_at)->toBeInstanceOf(Carbon::class)
            ->and($account->opened_at->toDateString())->toBe('2026-01-15');
    });
});

// ============================================================================
// SavingsTransaction - Additional Coverage
// ============================================================================
describe('SavingsTransaction additional coverage', function (): void {
    it('casts reversed_at as datetime', function (): void {
        $account = SavingsAccount::factory()->create();

        $txn = SavingsTransaction::create([
            'reference_number' => 'TXN-REV-001',
            'savings_account_id' => $account->id,
            'transaction_type' => SavingsTransactionType::Deposit,
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => now(),
            'value_date' => now(),
            'performed_by' => $this->user->id,
            'is_reversed' => true,
            'reversed_at' => now(),
            'reversed_by' => $this->user->id,
            'reversal_reason' => 'Error correction',
        ]);

        expect($txn->is_reversed)->toBeTrue()
            ->and($txn->reversed_at)->toBeInstanceOf(Carbon::class)
            ->and($txn->reversal_reason)->toBe('Error correction');
    });

    it('reverser relationship returns the user who reversed', function (): void {
        $account = SavingsAccount::factory()->create();
        $reverser = User::factory()->create();

        $txn = SavingsTransaction::create([
            'reference_number' => 'TXN-REV-002',
            'savings_account_id' => $account->id,
            'transaction_type' => SavingsTransactionType::Withdrawal,
            'amount' => 100000,
            'balance_before' => 1000000,
            'balance_after' => 900000,
            'transaction_date' => now(),
            'value_date' => now(),
            'performed_by' => $this->user->id,
            'is_reversed' => true,
            'reversed_by' => $reverser->id,
            'reversed_at' => now(),
        ]);

        expect($txn->reverser->id)->toBe($reverser->id);
    });

    it('handles different transaction types', function (): void {
        $account = SavingsAccount::factory()->create();

        $interest = SavingsTransaction::create([
            'reference_number' => 'TXN-INT-001',
            'savings_account_id' => $account->id,
            'transaction_type' => SavingsTransactionType::InterestCredit,
            'amount' => 5000,
            'balance_before' => 1000000,
            'balance_after' => 1005000,
            'transaction_date' => now(),
            'value_date' => now(),
            'performed_by' => $this->user->id,
            'is_reversed' => false,
        ]);

        expect($interest->transaction_type)->toBe(SavingsTransactionType::InterestCredit);
    });
});

// ============================================================================
// SavingsInterestAccrual - Additional Coverage
// ============================================================================
describe('SavingsInterestAccrual additional coverage', function (): void {
    it('casts posted_at as date', function (): void {
        $account = SavingsAccount::factory()->create();

        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'balance' => 10000000,
            'interest_rate' => 3.50000,
            'accrued_amount' => 958.90,
            'tax_amount' => 191.78,
            'is_posted' => true,
            'posted_at' => '2026-03-10',
        ]);

        expect($accrual->posted_at)->toBeInstanceOf(Carbon::class)
            ->and($accrual->posted_at->toDateString())->toBe('2026-03-10');
    });

    it('casts decimal fields correctly', function (): void {
        $account = SavingsAccount::factory()->create();

        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => now(),
            'balance' => 25000000.50,
            'interest_rate' => 4.75000,
            'accrued_amount' => 3253.42,
            'tax_amount' => 650.68,
            'is_posted' => false,
        ]);

        expect($accrual->balance)->toBe('25000000.50')
            ->and($accrual->interest_rate)->toBe('4.75000')
            ->and($accrual->accrued_amount)->toBe('3253.42')
            ->and($accrual->tax_amount)->toBe('650.68');
    });
});

// ============================================================================
// SavingsProduct - Additional Coverage
// ============================================================================
describe('SavingsProduct additional coverage', function (): void {
    it('casts decimal fields for fees correctly', function (): void {
        $product = SavingsProduct::factory()->create([
            'min_opening_balance' => 50000.00,
            'min_balance' => 25000.00,
            'max_balance' => 999999999.99,
            'admin_fee_monthly' => 5000.00,
            'closing_fee' => 10000.00,
            'dormant_fee' => 2500.00,
            'tax_rate' => 20.00000,
            'tax_threshold' => 7500000.00,
        ]);

        expect($product->min_opening_balance)->toBe('50000.00')
            ->and($product->min_balance)->toBe('25000.00')
            ->and($product->admin_fee_monthly)->toBe('5000.00')
            ->and($product->closing_fee)->toBe('10000.00')
            ->and($product->dormant_fee)->toBe('2500.00')
            ->and($product->tax_rate)->toBe('20.00000')
            ->and($product->tax_threshold)->toBe('7500000.00');
    });

    it('accounts relationship returns savings accounts', function (): void {
        $product = SavingsProduct::factory()->create();
        SavingsAccount::factory()->create(['savings_product_id' => $product->id]);
        SavingsAccount::factory()->create(['savings_product_id' => $product->id]);

        expect($product->accounts)->toHaveCount(2)
            ->and($product->accounts->first())->toBeInstanceOf(SavingsAccount::class);
    });
});
