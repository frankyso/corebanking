<?php

use App\Actions\Savings\CloseSavingsAccount;
use App\Actions\Savings\FreezeSavingsAccount;
use App\Actions\Savings\HoldSavingsBalance;
use App\Actions\Savings\MarkSavingsAccountDormant;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('CloseSavingsAccount', function (): void {
    beforeEach(function (): void {
        $this->action = app(CloseSavingsAccount::class);

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

    it('sets status to Closed and balance to 0', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        $transaction = $this->action->execute($account, $this->user);

        $account->refresh();

        expect($account->status)->toBe(SavingsAccountStatus::Closed)
            ->and((float) $account->balance)->toBe(0.00)
            ->and((float) $account->available_balance)->toBe(0.00)
            ->and($account->closed_at)->not->toBeNull()
            ->and($transaction)->not->toBeNull()
            ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Closing);
    });

    it('throws if account has hold amount', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 200000,
            performer: $this->user,
        ));

        app(HoldSavingsBalance::class)->execute($account, 50000, $this->user);
        $account->refresh();

        expect(fn () => $this->action->execute($account, $this->user))
            ->toThrow(InvalidSavingsAccountStatusException::class);
    });

    it('throws if account is Frozen', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        app(FreezeSavingsAccount::class)->execute($account);
        $account->refresh();

        expect(fn () => $this->action->execute($account, $this->user))
            ->toThrow(InvalidSavingsAccountStatusException::class);
    });

    it('allows closing a Dormant account', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        app(MarkSavingsAccountDormant::class)->execute($account);
        $account->refresh();

        $transaction = $this->action->execute($account, $this->user);

        expect($account->fresh()->status)->toBe(SavingsAccountStatus::Closed)
            ->and($transaction)->not->toBeNull();
    });
});
