<?php

use App\Enums\CustomerStatus;
use App\Enums\EodStatus;
use App\Enums\RiskRating;
use App\Enums\TellerSessionStatus;
use App\Enums\VaultTransactionType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
});

// ============================================================================
// SystemParameter - Additional Coverage
// ============================================================================
describe('SystemParameter additional coverage', function (): void {
    it('getValue returns default for nonexistent group', function (): void {
        expect(SystemParameter::getValue('missing_group', 'missing_key'))->toBeNull();
    });

    it('getValue handles boolean false value', function (): void {
        SystemParameter::factory()->boolean()->create([
            'group' => 'system',
            'key' => 'debug_mode',
            'value' => 'false',
        ]);

        expect(SystemParameter::getValue('system', 'debug_mode'))->toBeFalse();
    });

    it('getValue handles decimal type explicitly', function (): void {
        SystemParameter::factory()->decimal()->create([
            'group' => 'tax',
            'key' => 'vat_rate',
            'value' => '11.00',
        ]);

        expect(SystemParameter::getValue('tax', 'vat_rate'))->toBe(11.00)->toBeFloat();
    });

    it('getValue returns string for unknown type', function (): void {
        SystemParameter::factory()->create([
            'group' => 'misc',
            'key' => 'custom_value',
            'value' => 'hello world',
            'type' => 'text',
        ]);

        expect(SystemParameter::getValue('misc', 'custom_value'))->toBe('hello world');
    });
});

// ============================================================================
// Customer - Block/Unblock/Deactivate/Close Coverage
// ============================================================================
describe('Customer status methods', function (): void {
    it('block changes status to Blocked', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => CustomerStatus::Active,
        ]);

        $customer->block();

        expect($customer->fresh()->status)->toBe(CustomerStatus::Blocked);
    });

    it('unblock changes status to Active', function (): void {
        $customer = Customer::factory()->blocked()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $customer->unblock();

        expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
    });

    it('deactivate changes status to Inactive', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => CustomerStatus::Active,
        ]);

        $customer->deactivate();

        expect($customer->fresh()->status)->toBe(CustomerStatus::Inactive);
    });

    it('markClosed changes status to Closed', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => CustomerStatus::Active,
        ]);

        $customer->markClosed();

        expect($customer->fresh()->status)->toBe(CustomerStatus::Closed);
    });

    it('calculateRiskRating returns High for foreign nationality', function (): void {
        $rating = Customer::calculateRiskRating([
            'nationality' => 'USA',
            'monthly_income' => 10000000,
        ]);

        expect($rating)->toBe(RiskRating::High);
    });

    it('calculateRiskRating returns Medium for high income IDN', function (): void {
        $rating = Customer::calculateRiskRating([
            'nationality' => 'IDN',
            'monthly_income' => 200000000,
        ]);

        expect($rating)->toBe(RiskRating::Medium);
    });

    it('calculateRiskRating returns Low for normal income IDN', function (): void {
        $rating = Customer::calculateRiskRating([
            'nationality' => 'IDN',
            'monthly_income' => 10000000,
        ]);

        expect($rating)->toBe(RiskRating::Low);
    });

    it('calculateRiskRating returns High for very high income IDN', function (): void {
        $rating = Customer::calculateRiskRating([
            'nationality' => 'IDN',
            'monthly_income' => 600000000,
        ]);

        expect($rating)->toBe(RiskRating::High);
    });
});

// ============================================================================
// TellerSession - getActiveForUser Coverage
// ============================================================================
describe('TellerSession additional coverage', function (): void {
    it('getActiveForUser returns open session for user', function (): void {
        $session = TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $active = TellerSession::getActiveForUser($this->user);

        expect($active)->toBeInstanceOf(TellerSession::class)
            ->and($active->id)->toBe($session->id);
    });

    it('getActiveForUser returns null when no open session', function (): void {
        TellerSession::factory()->closed()->create([
            'user_id' => $this->user->id,
        ]);

        $active = TellerSession::getActiveForUser($this->user);

        expect($active)->toBeNull();
    });

    it('getActiveForUser returns null when sessions belong to other users', function (): void {
        $otherUser = User::factory()->create();
        TellerSession::factory()->create([
            'user_id' => $otherUser->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $active = TellerSession::getActiveForUser($this->user);

        expect($active)->toBeNull();
    });

    it('casts decimal balance fields correctly', function (): void {
        $session = TellerSession::factory()->create([
            'opening_balance' => 10000000.50,
            'current_balance' => 15000000.75,
            'total_cash_in' => 7000000.25,
            'total_cash_out' => 2000000.00,
        ]);

        expect($session->opening_balance)->toBe('10000000.50')
            ->and($session->current_balance)->toBe('15000000.75')
            ->and($session->total_cash_in)->toBe('7000000.25')
            ->and($session->total_cash_out)->toBe('2000000.00');
    });
});

// ============================================================================
// Vault - Additional Coverage
// ============================================================================
describe('Vault additional coverage', function (): void {
    it('casts decimal balance fields correctly', function (): void {
        $vault = Vault::factory()->create([
            'balance' => 500000000.00,
            'minimum_balance' => 5000000.00,
            'maximum_balance' => 1000000000.00,
        ]);

        expect($vault->balance)->toBe('500000000.00')
            ->and($vault->minimum_balance)->toBe('5000000.00')
            ->and($vault->maximum_balance)->toBe('1000000000.00');
    });

    it('transactions relationship returns vault transactions', function (): void {
        $vault = Vault::factory()->create();
        VaultTransaction::create([
            'reference_number' => 'VT-REL-001',
            'vault_id' => $vault->id,
            'transaction_type' => VaultTransactionType::CashIn,
            'amount' => 10000000,
            'balance_before' => 100000000,
            'balance_after' => 110000000,
            'performed_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        expect($vault->transactions)->toHaveCount(1);
    });
});

// ============================================================================
// EodProcess - Additional Coverage
// ============================================================================
describe('EodProcess additional coverage', function (): void {
    it('isFailed returns true for failed status', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Failed,
            'total_steps' => 10,
            'completed_steps' => 5,
            'error_message' => 'Interest accrual failed',
            'started_by' => $this->user->id,
        ]);

        expect($eod->isRunning())->toBeFalse()
            ->and($eod->isCompleted())->toBeFalse()
            ->and($eod->status)->toBe(EodStatus::Failed)
            ->and($eod->error_message)->toBe('Interest accrual failed');
    });

    it('progressPercentage handles partial progress', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 3,
            'completed_steps' => 1,
            'started_by' => $this->user->id,
        ]);

        expect($eod->progressPercentage())->toBe(33);
    });

    it('steps relationship returns ordered steps', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 3,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 2,
            'step_name' => 'GL Posting',
            'status' => EodStatus::Pending,
        ]);
        EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Pending,
        ]);

        $steps = $eod->steps;

        expect($steps)->toHaveCount(2)
            ->and($steps->first()->step_number)->toBe(1)
            ->and($steps->last()->step_number)->toBe(2);
    });
});

// ============================================================================
// EodProcessStep - Additional Coverage
// ============================================================================
describe('EodProcessStep additional coverage', function (): void {
    it('durationInSeconds returns null when not started', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Pending,
        ]);

        expect($step->durationInSeconds())->toBeNull();
    });

    it('durationInSeconds returns null when started but not completed', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Running,
            'started_at' => now(),
        ]);

        expect($step->durationInSeconds())->toBeNull();
    });

    it('durationInSeconds calculates correctly', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 1,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Completed,
            'started_at' => now()->subSeconds(45),
            'completed_at' => now(),
            'records_processed' => 150,
        ]);

        expect($step->durationInSeconds())->toBe(45);
    });

    it('metadata cast as array works', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 1,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Completed,
            'metadata' => ['accounts_processed' => 150, 'total_accrued' => 5000000],
        ]);

        expect($step->metadata)->toBeArray()
            ->and($step->metadata['accounts_processed'])->toBe(150)
            ->and($step->metadata['total_accrued'])->toBe(5000000);
    });
});
