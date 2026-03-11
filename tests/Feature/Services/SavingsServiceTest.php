<?php

use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Services\SavingsService;

describe('SavingsService', function (): void {
    beforeEach(function (): void {
        $this->service = app(SavingsService::class);

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

        $this->product = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'max_balance' => null,
            'closing_fee' => 25000,
        ]);
    });

    describe('open', function (): void {
        it('creates an active account with initial deposit and generated account number', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            expect($account)->toBeInstanceOf(SavingsAccount::class)
                ->and($account->status)->toBe(SavingsAccountStatus::Active)
                ->and((float) $account->balance)->toBe(100000.00)
                ->and((float) $account->available_balance)->toBe(100000.00)
                ->and((float) $account->hold_amount)->toBe(0.00)
                ->and($account->account_number)->toStartWith('T01001')
                ->and($account->customer_id)->toBe($this->customer->id)
                ->and($account->savings_product_id)->toBe($this->product->id);
        });

        it('creates an Opening transaction', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $transaction = $account->transactions()->first();

            expect($transaction)->not->toBeNull()
                ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Opening)
                ->and((float) $transaction->amount)->toBe(100000.00)
                ->and((float) $transaction->balance_before)->toBe(100000.00)
                ->and((float) $transaction->balance_after)->toBe(200000.00);
        });

        it('throws if initial deposit is below minimum opening balance', function (): void {
            expect(fn () => $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 10000,
                performer: $this->user,
            ))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('deposit', function (): void {
        beforeEach(function (): void {
            $this->account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );
        });

        it('increases balance and creates a deposit transaction', function (): void {
            $transaction = $this->service->deposit($this->account, 50000, $this->user);

            $this->account->refresh();

            expect((float) $this->account->balance)->toBe(150000.00)
                ->and((float) $this->account->available_balance)->toBe(150000.00)
                ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Deposit)
                ->and((float) $transaction->amount)->toBe(50000.00)
                ->and((float) $transaction->balance_before)->toBe(100000.00)
                ->and((float) $transaction->balance_after)->toBe(150000.00);
        });

        it('throws if amount is zero or negative', function (): void {
            expect(fn () => $this->service->deposit($this->account, 0, $this->user))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => $this->service->deposit($this->account, -100, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws if account is not active', function (): void {
            $this->account->update(['status' => SavingsAccountStatus::Closed]);

            expect(fn () => $this->service->deposit($this->account, 50000, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('resets dormant status on deposit', function (): void {
            $this->account->update([
                'status' => SavingsAccountStatus::Dormant,
                'dormant_at' => now(),
            ]);

            $this->service->deposit($this->account, 50000, $this->user);

            $this->account->refresh();

            expect($this->account->status)->toBe(SavingsAccountStatus::Active)
                ->and($this->account->dormant_at)->toBeNull();
        });
    });

    describe('withdraw', function (): void {
        beforeEach(function (): void {
            $this->account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 200000,
                performer: $this->user,
            );
        });

        it('decreases balance and creates a withdrawal transaction', function (): void {
            $transaction = $this->service->withdraw($this->account, 50000, $this->user);

            $this->account->refresh();

            expect((float) $this->account->balance)->toBe(150000.00)
                ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Withdrawal)
                ->and((float) $transaction->amount)->toBe(50000.00)
                ->and((float) $transaction->balance_before)->toBe(200000.00)
                ->and((float) $transaction->balance_after)->toBe(150000.00);
        });

        it('throws if insufficient balance', function (): void {
            expect(fn () => $this->service->withdraw($this->account, 999999, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws if remaining balance would be below min_balance', function (): void {
            // Balance = 200000, min_balance = 25000, so max withdrawal = 175000
            expect(fn () => $this->service->withdraw($this->account, 180000, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('allows withdrawal when remaining equals exactly min_balance', function (): void {
            // Balance = 200000, min_balance = 25000, withdraw 175000 => remaining = 25000
            $transaction = $this->service->withdraw($this->account, 175000, $this->user);

            $this->account->refresh();

            expect((float) $this->account->balance)->toBe(25000.00)
                ->and($transaction)->not->toBeNull();
        });

        it('throws if amount is zero or negative', function (): void {
            expect(fn () => $this->service->withdraw($this->account, 0, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws if account is not active', function (): void {
            $this->account->update(['status' => SavingsAccountStatus::Frozen]);

            expect(fn () => $this->service->withdraw($this->account, 50000, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('hold', function (): void {
        beforeEach(function (): void {
            $this->account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 200000,
                performer: $this->user,
            );
        });

        it('increases hold_amount and decreases available_balance', function (): void {
            $this->service->hold($this->account, 50000, $this->user);

            $this->account->refresh();

            expect((float) $this->account->hold_amount)->toBe(50000.00)
                ->and((float) $this->account->available_balance)->toBe(150000.00)
                ->and((float) $this->account->balance)->toBe(200000.00);
        });

        it('creates a Hold transaction', function (): void {
            $this->service->hold($this->account, 50000, $this->user);

            $transaction = $this->account->transactions()
                ->where('transaction_type', SavingsTransactionType::Hold)
                ->first();

            expect($transaction)->not->toBeNull()
                ->and((float) $transaction->amount)->toBe(50000.00);
        });

        it('throws if amount exceeds available balance', function (): void {
            expect(fn () => $this->service->hold($this->account, 999999, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('unhold', function (): void {
        beforeEach(function (): void {
            $this->account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 200000,
                performer: $this->user,
            );

            $this->service->hold($this->account, 50000, $this->user);
            $this->account->refresh();
        });

        it('decreases hold_amount and increases available_balance', function (): void {
            $this->service->unhold($this->account, 30000, $this->user);

            $this->account->refresh();

            expect((float) $this->account->hold_amount)->toBe(20000.00)
                ->and((float) $this->account->available_balance)->toBe(180000.00);
        });

        it('creates an Unhold transaction', function (): void {
            $this->service->unhold($this->account, 30000, $this->user);

            $transaction = $this->account->transactions()
                ->where('transaction_type', SavingsTransactionType::Unhold)
                ->first();

            expect($transaction)->not->toBeNull()
                ->and((float) $transaction->amount)->toBe(30000.00);
        });

        it('throws if amount exceeds hold_amount', function (): void {
            expect(fn () => $this->service->unhold($this->account, 999999, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('freeze', function (): void {
        it('sets status to Frozen', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->freeze($account);

            expect($account->fresh()->status)->toBe(SavingsAccountStatus::Frozen);
        });

        it('throws if account is not active', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );
            $account->update(['status' => SavingsAccountStatus::Closed]);

            expect(fn () => $this->service->freeze($account))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('unfreeze', function (): void {
        it('sets status back to Active when account is Frozen', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->freeze($account);
            $this->service->unfreeze($account);

            expect($account->fresh()->status)->toBe(SavingsAccountStatus::Active);
        });

        it('throws if account is not in Frozen status', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            expect(fn () => $this->service->unfreeze($account))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('close', function (): void {
        it('sets status to Closed and balance to 0', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $transaction = $this->service->close($account, $this->user);

            $account->refresh();

            expect($account->status)->toBe(SavingsAccountStatus::Closed)
                ->and((float) $account->balance)->toBe(0.00)
                ->and((float) $account->available_balance)->toBe(0.00)
                ->and($account->closed_at)->not->toBeNull()
                ->and($transaction)->not->toBeNull()
                ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Closing);
        });

        it('throws if account has hold amount', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 200000,
                performer: $this->user,
            );

            $this->service->hold($account, 50000, $this->user);
            $account->refresh();

            expect(fn () => $this->service->close($account, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws if account is Frozen', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->freeze($account);
            $account->refresh();

            expect(fn () => $this->service->close($account, $this->user))
                ->toThrow(InvalidArgumentException::class);
        });

        it('allows closing a Dormant account', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->markDormant($account);
            $account->refresh();

            $transaction = $this->service->close($account, $this->user);

            expect($account->fresh()->status)->toBe(SavingsAccountStatus::Closed)
                ->and($transaction)->not->toBeNull();
        });
    });

    describe('markDormant', function (): void {
        it('sets status to Dormant for Active account', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->markDormant($account);

            $account->refresh();

            expect($account->status)->toBe(SavingsAccountStatus::Dormant)
                ->and($account->dormant_at)->not->toBeNull();
        });

        it('does nothing for non-Active account', function (): void {
            $account = $this->service->open(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                initialDeposit: 100000,
                performer: $this->user,
            );

            $this->service->freeze($account);
            $account->refresh();

            $this->service->markDormant($account);

            expect($account->fresh()->status)->toBe(SavingsAccountStatus::Frozen);
        });
    });
});
