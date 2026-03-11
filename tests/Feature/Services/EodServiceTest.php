<?php

use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Enums\SavingsAccountStatus;
use App\Enums\TellerSessionStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\EodProcess;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;
use App\Services\EodService;
use Carbon\Carbon;

describe('EodService', function (): void {
    beforeEach(function (): void {
        $this->service = app(EodService::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'email' => 'admin@corebanking.test',
            'branch_id' => $this->branch->id,
        ]);

        $this->processDate = Carbon::parse('2026-03-10');
    });

    describe('run', function (): void {
        it('creates EodProcess and marks as Completed when all steps succeed', function (): void {
            $process = $this->service->run($this->processDate, $this->user);

            expect($process->status)->toBe(EodStatus::Completed)
                ->and($process->completed_at)->not->toBeNull()
                ->and($process->completed_steps)->toBe(11)
                ->and($process->total_steps)->toBe(11)
                ->and($process->started_by)->toBe($this->user->id);

            expect($process->steps()->count())->toBe(11);
        });

        it('throws when EOD for that date is already completed', function (): void {
            EodProcess::create([
                'process_date' => $this->processDate->toDateString(),
                'status' => EodStatus::Completed,
                'total_steps' => 11,
                'completed_steps' => 11,
                'started_by' => $this->user->id,
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);

            $this->service->run($this->processDate, $this->user);
        })->throws(InvalidArgumentException::class, 'EOD untuk tanggal');

        it('throws when EOD is currently running', function (): void {
            EodProcess::create([
                'process_date' => $this->processDate->toDateString(),
                'status' => EodStatus::Running,
                'total_steps' => 11,
                'completed_steps' => 3,
                'started_by' => $this->user->id,
                'started_at' => now(),
            ]);

            $this->service->run($this->processDate, $this->user);
        })->throws(InvalidArgumentException::class, 'EOD sedang berjalan');

        it('records each step with records_processed count', function (): void {
            $process = $this->service->run($this->processDate, $this->user);

            $steps = $process->steps()->orderBy('step_number')->get();
            expect($steps)->toHaveCount(11);

            foreach ($steps as $step) {
                expect($step->status)->toBe(EodStatus::Completed)
                    ->and($step->records_processed)->not->toBeNull();
            }
        });
    });

    describe('preCheck', function (): void {
        it('fails when open teller sessions exist', function (): void {
            $vault = Vault::factory()->create(['branch_id' => $this->branch->id]);

            TellerSession::create([
                'user_id' => $this->user->id,
                'branch_id' => $this->branch->id,
                'vault_id' => $vault->id,
                'status' => TellerSessionStatus::Open,
                'opening_balance' => 5000000,
                'current_balance' => 5000000,
                'total_cash_in' => 0,
                'total_cash_out' => 0,
                'transaction_count' => 0,
                'opened_at' => now(),
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            expect($process->status)->toBe(EodStatus::Failed)
                ->and($process->error_message)->toContain('sesi teller');
        });
    });

    describe('accrueSavingsInterest', function (): void {
        it('processes all active savings accounts', function (): void {
            $product = SavingsProduct::factory()->create([
                'code' => 'SAV',
                'interest_rate' => 3.5,
            ]);

            SavingsAccount::create([
                'account_number' => 'SAV001000000001',
                'customer_id' => Customer::factory()->create([
                    'branch_id' => $this->branch->id,
                    'created_by' => $this->user->id,
                    'approved_by' => $this->user->id,
                ])->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 10000000,
                'hold_amount' => 0,
                'opened_at' => now()->subMonths(3),
                'last_transaction_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            $step2 = $process->steps()->where('step_number', 2)->first();
            expect($step2->status)->toBe(EodStatus::Completed)
                ->and($step2->records_processed)->toBe(1);
        });
    });

    describe('accrueDepositInterest', function (): void {
        it('processes all active deposit accounts', function (): void {
            $product = DepositProduct::factory()->create(['code' => 'DEP']);

            DepositProductRate::create([
                'deposit_product_id' => $product->id,
                'tenor_months' => 12,
                'min_amount' => 1000000,
                'max_amount' => null,
                'interest_rate' => 6.0,
                'is_active' => true,
            ]);

            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            DepositAccount::factory()->create([
                'customer_id' => $customer->id,
                'deposit_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => DepositStatus::Active,
                'principal_amount' => 10000000,
                'interest_rate' => 6.0,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            $step3 = $process->steps()->where('step_number', 3)->first();
            expect($step3->status)->toBe(EodStatus::Completed)
                ->and($step3->records_processed)->toBe(1);
        });
    });

    describe('accrueLoanInterest', function (): void {
        it('accrues daily loan interest on all active loans', function (): void {
            $product = LoanProduct::factory()->create(['code' => 'KMK']);
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            $account = LoanAccount::factory()->create([
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'status' => LoanStatus::Active,
                'outstanding_principal' => 10000000,
                'interest_rate' => 12.0,
                'accrued_interest' => 0,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            $step4 = $process->steps()->where('step_number', 4)->first();
            expect($step4->status)->toBe(EodStatus::Completed)
                ->and($step4->records_processed)->toBe(1);

            $account->refresh();
            expect((float) $account->accrued_interest)->toBeGreaterThan(0);
        });
    });

    describe('updateDpd and updateCollectibility', function (): void {
        it('updates DPD and collectibility for overdue loan accounts', function (): void {
            $product = LoanProduct::factory()->create(['code' => 'KRD']);
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            $account = LoanAccount::factory()->create([
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'status' => LoanStatus::Active,
                'outstanding_principal' => 10000000,
                'dpd' => 0,
            ]);

            LoanSchedule::factory()->create([
                'loan_account_id' => $account->id,
                'due_date' => now()->subDays(30),
                'is_paid' => false,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            $step7 = $process->steps()->where('step_number', 7)->first();
            expect($step7->records_processed)->toBe(1);

            $account->refresh();
            expect($account->dpd)->toBeGreaterThan(0);
        });
    });

    describe('checkDormancy', function (): void {
        it('marks accounts as dormant when last transaction exceeds dormancy period', function (): void {
            SystemParameter::create([
                'group' => 'savings',
                'key' => 'dormant_period_days',
                'value' => '180',
                'type' => 'integer',
            ]);

            $product = SavingsProduct::factory()->create(['code' => 'DOR']);
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            SavingsAccount::create([
                'account_number' => 'DOR001000000001',
                'customer_id' => $customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => 100000,
                'hold_amount' => 0,
                'opened_at' => now()->subYear(),
                'last_transaction_at' => now()->subDays(200),
                'created_by' => $this->user->id,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            $step10 = $process->steps()->where('step_number', 10)->first();
            expect($step10->records_processed)->toBe(1);

            $dormantAccount = SavingsAccount::where('account_number', 'DOR001000000001')->first();
            expect($dormantAccount->status)->toBe(SavingsAccountStatus::Dormant);
        });
    });

    describe('postValidation', function (): void {
        it('fails when negative savings balances exist', function (): void {
            $product = SavingsProduct::factory()->create(['code' => 'NEG']);
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            SavingsAccount::create([
                'account_number' => 'NEG001000000001',
                'customer_id' => $customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'status' => SavingsAccountStatus::Active,
                'balance' => -50000,
                'hold_amount' => 0,
                'opened_at' => now()->subMonth(),
                'last_transaction_at' => now(),
                'created_by' => $this->user->id,
            ]);

            $process = $this->service->run($this->processDate, $this->user);

            expect($process->status)->toBe(EodStatus::Failed)
                ->and($process->error_message)->toContain('saldo negatif');
        });
    });

    describe('getStepNames', function (): void {
        it('returns all 11 step names', function (): void {
            $steps = $this->service->getStepNames();

            expect($steps)->toHaveCount(11)
                ->and($steps[1])->toBe('Pre-check Validation')
                ->and($steps[11])->toBe('Post-validation');
        });
    });
});
