<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerLoanPayment;
use App\DTOs\Teller\OpenTellerSessionData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\TellerTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\User;
use App\Models\Vault;

describe('ProcessTellerLoanPayment', function (): void {
    beforeEach(function (): void {
        $this->action = app(ProcessTellerLoanPayment::class);

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

    it('processes loan payment and creates teller transaction', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $product = LoanProduct::factory()->create(['code' => 'KMK']);
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->teller->id,
            'outstanding_principal' => 10000000,
        ]);

        LoanSchedule::factory()->create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 1,
            'due_date' => now()->addMonth(),
            'principal_amount' => 900000,
            'interest_amount' => 100000,
            'total_amount' => 1000000,
            'is_paid' => false,
        ]);

        $tx = $this->action->execute(
            new TellerTransactionData(
                session: $session,
                amount: 1000000,
                performer: $this->teller,
            ),
            $loanAccount,
        );

        expect($tx->transaction_type)->toBe(TellerTransactionType::LoanPayment)
            ->and($tx->direction)->toBe('in')
            ->and((float) $tx->amount)->toBe(1000000.00);

        $session->refresh();
        expect((float) $session->current_balance)->toBe(6000000.00);
    });
});
