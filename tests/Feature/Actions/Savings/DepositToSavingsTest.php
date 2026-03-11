<?php

use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('DepositToSavings', function (): void {
    beforeEach(function (): void {
        $this->action = app(DepositToSavings::class);

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
            initialDeposit: 100000,
            performer: $this->user,
        ));
    });

    it('increases balance and creates a deposit transaction', function (): void {
        $transaction = $this->action->execute($this->account, 50000, $this->user);

        $this->account->refresh();

        expect((float) $this->account->balance)->toBe(150000.00)
            ->and((float) $this->account->available_balance)->toBe(150000.00)
            ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Deposit)
            ->and((float) $transaction->amount)->toBe(50000.00)
            ->and((float) $transaction->balance_before)->toBe(100000.00)
            ->and((float) $transaction->balance_after)->toBe(150000.00);
    });

    it('throws if amount is zero or negative', function (): void {
        expect(fn () => $this->action->execute($this->account, 0, $this->user))
            ->toThrow(InsufficientSavingsBalanceException::class);

        expect(fn () => $this->action->execute($this->account, -100, $this->user))
            ->toThrow(InsufficientSavingsBalanceException::class);
    });

    it('throws if account is not active', function (): void {
        $this->account->update(['status' => SavingsAccountStatus::Closed]);

        expect(fn () => $this->action->execute($this->account, 50000, $this->user))
            ->toThrow(InvalidSavingsAccountStatusException::class);
    });

    it('resets dormant status on deposit', function (): void {
        $this->account->update([
            'status' => SavingsAccountStatus::Dormant,
            'dormant_at' => now(),
        ]);

        $this->action->execute($this->account, 50000, $this->user);

        $this->account->refresh();

        expect($this->account->status)->toBe(SavingsAccountStatus::Active)
            ->and($this->account->dormant_at)->toBeNull();
    });
});
