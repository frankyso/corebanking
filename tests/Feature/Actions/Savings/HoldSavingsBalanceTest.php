<?php

use App\Actions\Savings\HoldSavingsBalance;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('HoldSavingsBalance', function (): void {
    beforeEach(function (): void {
        $this->action = app(HoldSavingsBalance::class);

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

    it('increases hold_amount and decreases available_balance', function (): void {
        $this->action->execute($this->account, 50000, $this->user);

        $this->account->refresh();

        expect((float) $this->account->hold_amount)->toBe(50000.00)
            ->and((float) $this->account->available_balance)->toBe(150000.00)
            ->and((float) $this->account->balance)->toBe(200000.00);
    });

    it('creates a Hold transaction', function (): void {
        $this->action->execute($this->account, 50000, $this->user);

        $transaction = $this->account->transactions()
            ->where('transaction_type', SavingsTransactionType::Hold)
            ->first();

        expect($transaction)->not->toBeNull()
            ->and((float) $transaction->amount)->toBe(50000.00);
    });

    it('throws if amount exceeds available balance', function (): void {
        expect(fn () => $this->action->execute($this->account, 999999, $this->user))
            ->toThrow(InsufficientSavingsBalanceException::class);
    });
});
