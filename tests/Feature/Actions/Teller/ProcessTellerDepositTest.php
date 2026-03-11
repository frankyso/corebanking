<?php

use App\Actions\Teller\CloseTellerSession;
use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerDeposit;
use App\DTOs\Teller\OpenTellerSessionData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\SavingsAccountStatus;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use App\Models\Vault;

describe('ProcessTellerDeposit', function (): void {
    beforeEach(function (): void {
        $this->action = app(ProcessTellerDeposit::class);

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
            'balance' => 100000,
            'available_balance' => 100000,
            'hold_amount' => 0,
            'opened_at' => now(),
            'last_transaction_at' => now(),
            'created_by' => $this->teller->id,
        ]);
    });

    it('deposits into savings account and creates teller transaction', function (): void {
        $tx = $this->action->execute(
            new TellerTransactionData(
                session: $this->session,
                amount: 500000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );

        expect($tx->transaction_type)->toBe(TellerTransactionType::SavingsDeposit)
            ->and($tx->direction)->toBe('in')
            ->and((float) $tx->amount)->toBe(500000.00);

        $this->savingsAccount->refresh();
        expect((float) $this->savingsAccount->balance)->toBe(600000.00);

        $this->session->refresh();
        expect((float) $this->session->current_balance)->toBe(5500000.00)
            ->and($this->session->transaction_count)->toBe(1)
            ->and((float) $this->session->total_cash_in)->toBe(500000.00);
    });

    it('throws when session is not open', function (): void {
        app(CloseTellerSession::class)->execute($this->session, $this->teller);

        $this->action->execute(
            new TellerTransactionData(
                session: $this->session->fresh(),
                amount: 500000,
                performer: $this->teller,
            ),
            $this->savingsAccount,
        );
    })->throws(TellerSessionClosedException::class, 'Sesi teller tidak aktif');
});
