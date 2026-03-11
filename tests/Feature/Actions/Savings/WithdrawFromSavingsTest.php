<?php

use App\Actions\Savings\OpenSavingsAccount;
use App\Actions\Savings\WithdrawFromSavings;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('WithdrawFromSavings', function (): void {
    beforeEach(function (): void {
        $this->action = app(WithdrawFromSavings::class);

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

        $this->account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 200000,
            performer: $this->user,
        ));
    });

    it('decreases balance and creates a withdrawal transaction', function (): void {
        $transaction = $this->action->execute($this->account, 50000, $this->user);

        $this->account->refresh();

        expect((float) $this->account->balance)->toBe(150000.00)
            ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Withdrawal)
            ->and((float) $transaction->amount)->toBe(50000.00)
            ->and((float) $transaction->balance_before)->toBe(200000.00)
            ->and((float) $transaction->balance_after)->toBe(150000.00);
    });

    it('throws if insufficient balance', function (): void {
        expect(fn () => $this->action->execute($this->account, 999999, $this->user))
            ->toThrow(InsufficientSavingsBalanceException::class);
    });

    it('throws if remaining balance would be below min_balance', function (): void {
        // Balance = 200000, min_balance = 25000, so max withdrawal = 175000
        expect(fn () => $this->action->execute($this->account, 180000, $this->user))
            ->toThrow(SavingsBalanceLimitException::class);
    });

    it('allows withdrawal when remaining equals exactly min_balance', function (): void {
        // Balance = 200000, min_balance = 25000, withdraw 175000 => remaining = 25000
        $transaction = $this->action->execute($this->account, 175000, $this->user);

        $this->account->refresh();

        expect((float) $this->account->balance)->toBe(25000.00)
            ->and($transaction)->not->toBeNull();
    });

    it('throws if amount is zero or negative', function (): void {
        expect(fn () => $this->action->execute($this->account, 0, $this->user))
            ->toThrow(InsufficientSavingsBalanceException::class);
    });

    it('throws if account is not active', function (): void {
        $this->account->update(['status' => SavingsAccountStatus::Frozen]);

        expect(fn () => $this->action->execute($this->account, 50000, $this->user))
            ->toThrow(InvalidSavingsAccountStatusException::class);
    });
});
