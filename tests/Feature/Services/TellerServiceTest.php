<?php

use App\Enums\SavingsAccountStatus;
use App\Enums\TellerSessionStatus;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Services\TellerService;

describe('TellerService', function (): void {
    beforeEach(function (): void {
        $this->service = app(TellerService::class);

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

    describe('openSession', function (): void {
        it('creates an open teller session with correct opening balance', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

            expect($session)->toBeInstanceOf(TellerSession::class)
                ->and($session->status)->toBe(TellerSessionStatus::Open)
                ->and((float) $session->opening_balance)->toBe(5000000.00)
                ->and((float) $session->current_balance)->toBe(5000000.00)
                ->and((float) $session->total_cash_in)->toBe(0.00)
                ->and((float) $session->total_cash_out)->toBe(0.00)
                ->and($session->transaction_count)->toBe(0)
                ->and($session->user_id)->toBe($this->teller->id);
        });

        it('creates a vault transaction for initial cash draw', function (): void {
            $vaultBalanceBefore = (float) $this->vault->balance;
            $this->service->openSession($this->teller, $this->vault, 5000000);

            $this->vault->refresh();
            expect((float) $this->vault->balance)->toBe($vaultBalanceBefore - 5000000);

            $vaultTx = VaultTransaction::where('vault_id', $this->vault->id)->first();
            expect($vaultTx->transaction_type)->toBe(VaultTransactionType::TellerRequest);
        });

        it('throws when teller already has an active session', function (): void {
            $this->service->openSession($this->teller, $this->vault, 5000000);

            $this->service->openSession($this->teller, $this->vault, 3000000);
        })->throws(InvalidArgumentException::class, 'Teller sudah memiliki sesi aktif');
    });

    describe('closeSession', function (): void {
        it('closes session and returns cash to vault', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);
            $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

            $closed = $this->service->closeSession($session, $this->teller, 'Tutup shift');

            expect($closed->status)->toBe(TellerSessionStatus::Closed)
                ->and((float) $closed->closing_balance)->toBe(5000000.00)
                ->and($closed->closed_at)->not->toBeNull()
                ->and($closed->closing_notes)->toBe('Tutup shift');

            $this->vault->refresh();
            expect((float) $this->vault->balance)->toBe($vaultBalanceBefore + 5000000);
        });

        it('throws when session is already closed', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);
            $this->service->closeSession($session, $this->teller);

            $this->service->closeSession($session->fresh(), $this->teller);
        })->throws(InvalidArgumentException::class, 'Sesi sudah ditutup');
    });

    describe('processDeposit', function (): void {
        beforeEach(function (): void {
            $this->session = $this->service->openSession($this->teller, $this->vault, 5000000);

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
            $tx = $this->service->processDeposit(
                session: $this->session,
                account: $this->savingsAccount,
                amount: 500000,
                performer: $this->teller,
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
            $this->service->closeSession($this->session, $this->teller);

            $this->service->processDeposit(
                session: $this->session->fresh(),
                account: $this->savingsAccount,
                amount: 500000,
                performer: $this->teller,
            );
        })->throws(InvalidArgumentException::class, 'Sesi teller tidak aktif');
    });

    describe('processWithdrawal', function (): void {
        beforeEach(function (): void {
            $this->session = $this->service->openSession($this->teller, $this->vault, 5000000);

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
            $tx = $this->service->processWithdrawal(
                session: $this->session,
                account: $this->savingsAccount,
                amount: 200000,
                performer: $this->teller,
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
            $this->service->processWithdrawal(
                session: $this->session,
                account: $this->savingsAccount,
                amount: 6000000,
                performer: $this->teller,
            );
        })->throws(InvalidArgumentException::class, 'Saldo kas teller tidak mencukupi');
    });

    describe('processLoanPayment', function (): void {
        it('processes loan payment and creates teller transaction', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

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

            $tx = $this->service->processLoanPayment(
                session: $session,
                loanAccount: $loanAccount,
                amount: 1000000,
                performer: $this->teller,
            );

            expect($tx->transaction_type)->toBe(TellerTransactionType::LoanPayment)
                ->and($tx->direction)->toBe('in')
                ->and((float) $tx->amount)->toBe(1000000.00);

            $session->refresh();
            expect((float) $session->current_balance)->toBe(6000000.00);
        });
    });

    describe('reverseTransaction', function (): void {
        it('creates a reverse transaction with opposite direction', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

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

            $depositTx = $this->service->processDeposit(
                session: $session,
                account: $savingsAccount,
                amount: 100000,
                performer: $this->teller,
            );

            $reversalTx = $this->service->reverseTransaction($depositTx, $this->teller, 'Salah input');

            expect($reversalTx->direction)->toBe('out')
                ->and((float) $reversalTx->amount)->toBe(100000.00);

            $depositTx->refresh();
            expect($depositTx->is_reversed)->toBeTrue();
        });

        it('throws when transaction is already reversed', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

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

            $tx = $this->service->processDeposit($session, $savingsAccount, 100000, $this->teller);
            $this->service->reverseTransaction($tx, $this->teller, 'Error');

            $this->service->reverseTransaction($tx->fresh(), $this->teller, 'Error lagi');
        })->throws(InvalidArgumentException::class, 'Transaksi sudah pernah dibatalkan');
    });

    describe('requestCash', function (): void {
        it('creates vault and teller transactions for cash request', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);
            $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

            $tx = $this->service->requestCash($session, $this->vault, 3000000, $this->teller);

            expect($tx->transaction_type)->toBe(TellerTransactionType::CashRequest)
                ->and($tx->direction)->toBe('in')
                ->and((float) $tx->amount)->toBe(3000000.00);

            $session->refresh();
            expect((float) $session->current_balance)->toBe(8000000.00);

            $this->vault->refresh();
            expect((float) $this->vault->balance)->toBe($vaultBalanceBefore - 3000000);
        });
    });

    describe('returnCash', function (): void {
        it('creates vault and teller transactions for cash return', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);
            $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

            $tx = $this->service->returnCash($session, $this->vault, 2000000, $this->teller);

            expect($tx->transaction_type)->toBe(TellerTransactionType::CashReturn)
                ->and($tx->direction)->toBe('out')
                ->and((float) $tx->amount)->toBe(2000000.00);

            $session->refresh();
            expect((float) $session->current_balance)->toBe(3000000.00);

            $this->vault->refresh();
            expect((float) $this->vault->balance)->toBe($vaultBalanceBefore + 2000000);
        });

        it('throws when amount exceeds session current balance', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

            $this->service->returnCash($session, $this->vault, 6000000, $this->teller);
        })->throws(InvalidArgumentException::class, 'Saldo kas teller tidak mencukupi');
    });

    describe('needsAuthorization', function (): void {
        it('returns true when amount is at or above limit', function (): void {
            SystemParameter::create([
                'group' => 'teller',
                'key' => 'authorization_limit',
                'value' => '50000000',
                'type' => 'decimal',
            ]);

            expect($this->service->needsAuthorization(50000000))->toBeTrue()
                ->and($this->service->needsAuthorization(60000000))->toBeTrue();
        });

        it('returns false when amount is below limit', function (): void {
            SystemParameter::create([
                'group' => 'teller',
                'key' => 'authorization_limit',
                'value' => '50000000',
                'type' => 'decimal',
            ]);

            expect($this->service->needsAuthorization(49999999))->toBeFalse();
        });

        it('uses default limit when system parameter does not exist', function (): void {
            // Default is 100000000
            expect($this->service->needsAuthorization(100000000))->toBeTrue()
                ->and($this->service->needsAuthorization(99999999))->toBeFalse();
        });
    });

    describe('getActiveSession', function (): void {
        it('returns open session for the teller', function (): void {
            $session = $this->service->openSession($this->teller, $this->vault, 5000000);

            $active = $this->service->getActiveSession($this->teller);

            expect($active)->not->toBeNull()
                ->and($active->id)->toBe($session->id);
        });

        it('returns null when teller has no open session', function (): void {
            $active = $this->service->getActiveSession($this->teller);

            expect($active)->toBeNull();
        });
    });
});
