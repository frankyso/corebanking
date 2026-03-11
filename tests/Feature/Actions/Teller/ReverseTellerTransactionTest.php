<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerDeposit;
use App\Actions\Teller\ReverseTellerTransaction;
use App\DTOs\Teller\OpenTellerSessionData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Models\Vault;

describe('ReverseTellerTransaction', function (): void {
    beforeEach(function (): void {
        $this->action = app(ReverseTellerTransaction::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->teller = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->teller->id,
            'approved_by' => $this->teller->id,
        ]);

        $this->vault = Vault::factory()->create([
            'branch_id' => $this->branch->id,
            'balance' => 100000000,
        ]);
    });

    it('creates a reverse transaction with opposite direction', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $product = SavingsProduct::factory()->create([
            'code' => 'T03',
            'min_opening_balance' => 50000,
            'min_balance' => 10000,
        ]);

        $savingsAccount = SavingsAccount::create([
            'account_number' => 'T03001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 500000,
            'available_balance' => 500000,
            'hold_amount' => 0,
            'opened_at' => now(),
            'last_transaction_at' => now(),
            'created_by' => $this->teller->id,
        ]);

        $depositTx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $session,
                amount: 100000,
                performer: $this->teller,
            ),
            $savingsAccount,
        );

        $reversalTx = $this->action->execute($depositTx, $this->teller, 'Salah input');

        expect($reversalTx->direction)->toBe('out')
            ->and((float) $reversalTx->amount)->toBe(100000.00);

        $depositTx->refresh();
        expect($depositTx->is_reversed)->toBeTrue();
    });

    it('throws when transaction is already reversed', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $product = SavingsProduct::factory()->create([
            'code' => 'T04',
            'min_opening_balance' => 50000,
            'min_balance' => 10000,
        ]);

        $savingsAccount = SavingsAccount::create([
            'account_number' => 'T04001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 500000,
            'available_balance' => 500000,
            'hold_amount' => 0,
            'opened_at' => now(),
            'last_transaction_at' => now(),
            'created_by' => $this->teller->id,
        ]);

        $tx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $session,
                amount: 100000,
                performer: $this->teller,
            ),
            $savingsAccount,
        );
        $this->action->execute($tx, $this->teller, 'Error');

        $this->action->execute($tx->fresh(), $this->teller, 'Error lagi');
    })->throws(InvalidArgumentException::class, 'Transaksi sudah pernah dibatalkan');
});
