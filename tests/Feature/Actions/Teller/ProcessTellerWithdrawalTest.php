<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerWithdrawal;
use App\DTOs\Teller\OpenTellerSessionData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\SavingsAccountStatus;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Models\Vault;

describe('ProcessTellerWithdrawal', function (): void {
    beforeEach(function (): void {
        $this->action = app(ProcessTellerWithdrawal::class);

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
            'code' => 'T02',
            'min_opening_balance' => 50000,
            'min_balance' => 10000,
        ]);

        $this->savingsAccount = SavingsAccount::create([
            'account_number' => 'T02001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 10000000,
            'available_balance' => 10000000,
            'hold_amount' => 0,
            'opened_at' => now(),
            'last_transaction_at' => now(),
            'created_by' => $this->teller->id,
        ]);
    });

    it('withdraws from savings account and creates teller transaction', function (): void {
        $tx = $this->action->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 200000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($tx->transaction_type)->toBe(TellerTransactionType::SavingsWithdrawal)
            ->and($tx->direction)->toBe('out')
            ->and((float) $tx->amount)->toBe(200000.00);

        $this->savingsAccount->refresh();
        expect((float) $this->savingsAccount->balance)->toBe(9800000.00);

        $this->session->refresh();
        expect((float) $this->session->current_balance)->toBe(4800000.00)
            ->and((float) $this->session->total_cash_out)->toBe(200000.00);
    });

    it('throws when session teller cash is insufficient', function (): void {
        $this->action->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 6000000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );
    })->throws(InsufficientTellerCashException::class, 'Saldo kas teller tidak mencukupi');
});
