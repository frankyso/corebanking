<?php

use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\OpenSavingsAccount;
use App\Actions\Savings\WithdrawFromSavings;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('CreatesSavingsTransaction concern', function (): void {
    beforeEach(function (): void {
        $this->depositAction = app(DepositToSavings::class);
        $this->withdrawAction = app(WithdrawFromSavings::class);

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
            initialDeposit: 500000,
            performer: $this->user,
        ));
    });

    it('creates credit transaction with correct balance_after for deposit', function (): void {
        $transaction = $this->depositAction->execute($this->account, 200000, $this->user);

        expect($transaction->transaction_type)->toBe(SavingsTransactionType::Deposit)
            ->and((float) $transaction->balance_before)->toBe(500000.00)
            ->and((float) $transaction->balance_after)->toBe(700000.00)
            ->and((float) $transaction->amount)->toBe(200000.00);
    });

    it('creates debit transaction with correct balance_after for withdrawal', function (): void {
        $transaction = $this->withdrawAction->execute($this->account, 100000, $this->user);

        expect($transaction->transaction_type)->toBe(SavingsTransactionType::Withdrawal)
            ->and((float) $transaction->balance_before)->toBe(500000.00)
            ->and((float) $transaction->balance_after)->toBe(400000.00)
            ->and((float) $transaction->amount)->toBe(100000.00);
    });

    it('generates reference number starting with TRX prefix', function (): void {
        $transaction = $this->depositAction->execute($this->account, 100000, $this->user);

        expect($transaction->reference_number)->toStartWith('TRX')
            ->and(strlen($transaction->reference_number))->toBe(17); // TRX + 8 date digits + 6 random digits
    });

    it('records performer and transaction date correctly', function (): void {
        $transaction = $this->depositAction->execute($this->account, 150000, $this->user);

        expect($transaction->performed_by)->toBe($this->user->id)
            ->and($transaction->transaction_date->format('Y-m-d'))->toBe(now()->toDateString())
            ->and($transaction->value_date->format('Y-m-d'))->toBe(now()->toDateString())
            ->and($transaction->savings_account_id)->toBe($this->account->id);
    });

    it('tracks balance progression across multiple transactions', function (): void {
        $tx1 = $this->depositAction->execute($this->account, 100000, $this->user);
        $this->account->refresh();

        $tx2 = $this->withdrawAction->execute($this->account, 50000, $this->user);

        expect((float) $tx1->balance_before)->toBe(500000.00)
            ->and((float) $tx1->balance_after)->toBe(600000.00)
            ->and((float) $tx2->balance_before)->toBe(600000.00)
            ->and((float) $tx2->balance_after)->toBe(550000.00);
    });
});
