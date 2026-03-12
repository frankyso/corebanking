<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerDeposit;
use App\Actions\Teller\ProcessTellerWithdrawal;
use App\Actions\Teller\RequestTellerCash;
use App\DTOs\Teller\OpenTellerSessionData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\SavingsAccountStatus;
use App\Enums\VaultTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SystemParameter;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;

describe('CreatesTellerTransactions concern', function (): void {
    beforeEach(function (): void {
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

        $this->session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $product = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
        ]);

        $this->savingsAccount = SavingsAccount::create([
            'account_number' => 'T01001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 1000000,
            'available_balance' => 1000000,
            'hold_amount' => 0,
            'opened_at' => now(),
            'last_transaction_at' => now(),
            'created_by' => $this->teller->id,
        ]);
    });

    it('increments session balance for in-direction teller transaction', function (): void {
        $tx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 500000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($tx->direction)->toBe('in')
            ->and((float) $tx->teller_balance_before)->toBe(5000000.00)
            ->and((float) $tx->teller_balance_after)->toBe(5500000.00);

        $this->session->refresh();
        expect((float) $this->session->current_balance)->toBe(5500000.00)
            ->and((float) $this->session->total_cash_in)->toBe(500000.00)
            ->and($this->session->transaction_count)->toBe(1);
    });

    it('decrements session balance for out-direction teller transaction', function (): void {
        $tx = app(ProcessTellerWithdrawal::class)->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 200000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($tx->direction)->toBe('out')
            ->and((float) $tx->teller_balance_before)->toBe(5000000.00)
            ->and((float) $tx->teller_balance_after)->toBe(4800000.00);

        $this->session->refresh();
        expect((float) $this->session->current_balance)->toBe(4800000.00)
            ->and((float) $this->session->total_cash_out)->toBe(200000.00)
            ->and($this->session->transaction_count)->toBe(1);
    });

    it('creates vault transaction for cash movements', function (): void {
        $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

        app(RequestTellerCash::class)->execute($this->session, $this->vault, 2000000, $this->teller);

        $vaultTx = VaultTransaction::where('vault_id', $this->vault->id)
            ->where('transaction_type', VaultTransactionType::TellerRequest)
            ->latest('id')
            ->first();

        expect($vaultTx)->not->toBeNull()
            ->and((float) $vaultTx->amount)->toBe(2000000.00)
            ->and((float) $vaultTx->balance_before)->toBe($vaultBalanceBefore)
            ->and((float) $vaultTx->balance_after)->toBe($vaultBalanceBefore - 2000000)
            ->and($vaultTx->reference_number)->toStartWith('VLT');

        $this->vault->refresh();
        expect((float) $this->vault->balance)->toBe($vaultBalanceBefore - 2000000);
    });

    it('returns correct needsAuthorization based on amount vs limit', function (): void {
        SystemParameter::create([
            'group' => 'teller',
            'key' => 'authorization_limit',
            'value' => '1000000',
            'type' => 'number',
            'description' => 'Batas otorisasi teller',
            'is_editable' => true,
        ]);

        $smallTx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 500000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($smallTx->needs_authorization)->toBeFalse();

        $this->session->refresh();

        $largeTx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 1000000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($largeTx->needs_authorization)->toBeTrue();
    });

    it('generates teller reference number starting with TLR prefix', function (): void {
        $tx = app(ProcessTellerDeposit::class)->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 100000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($tx->reference_number)->toStartWith('TLR')
            ->and(strlen($tx->reference_number))->toBe(17); // TLR + 8 date digits + 6 random digits
    });
});
