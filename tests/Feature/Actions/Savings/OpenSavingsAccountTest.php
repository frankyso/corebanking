<?php

use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;

describe('OpenSavingsAccount', function (): void {
    beforeEach(function (): void {
        $this->action = app(OpenSavingsAccount::class);

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

    it('creates an active account with initial deposit and generated account number', function (): void {
        $account = $this->action->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

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
        $account = $this->action->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        $transaction = $account->transactions()->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->transaction_type)->toBe(SavingsTransactionType::Opening)
            ->and((float) $transaction->amount)->toBe(100000.00)
            ->and((float) $transaction->balance_before)->toBe(100000.00)
            ->and((float) $transaction->balance_after)->toBe(200000.00);
    });

    it('throws if initial deposit is below minimum opening balance', function (): void {
        expect(fn () => $this->action->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 10000,
            performer: $this->user,
        )))->toThrow(SavingsBalanceLimitException::class);
    });
});
