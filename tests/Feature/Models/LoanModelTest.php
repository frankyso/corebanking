<?php

use App\Enums\CollateralType;
use App\Enums\Collectibility;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanCollateral;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    $this->customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);
});

// ============================================================================
// LoanAccount - Additional Scope & Business Method Coverage
// ============================================================================
describe('LoanAccount additional coverage', function (): void {
    it('scope active includes Current and Overdue statuses', function (): void {
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Current,
        ]);
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Overdue,
        ]);
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::WrittenOff,
        ]);

        $active = LoanAccount::active()->get();

        expect($active)->toHaveCount(2)
            ->and($active->pluck('status')->unique()->sort()->values()->all())
            ->toContain(LoanStatus::Current)
            ->toContain(LoanStatus::Overdue);
    });

    it('scope byCollectibility filters Substandard', function (): void {
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'collectibility' => Collectibility::Substandard,
        ]);
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'collectibility' => Collectibility::Current,
        ]);

        $substandard = LoanAccount::byCollectibility(Collectibility::Substandard)->get();

        expect($substandard)->toHaveCount(1)
            ->and($substandard->first()->collectibility)->toBe(Collectibility::Substandard);
    });

    it('casts decimal fields correctly', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'principal_amount' => 50000000.00,
            'interest_rate' => 12.50000,
            'outstanding_principal' => 45000000.00,
            'outstanding_interest' => 500000.00,
            'accrued_interest' => 150000.00,
            'total_principal_paid' => 5000000.00,
            'total_interest_paid' => 3000000.00,
            'total_penalty_paid' => 100000.00,
            'ckpn_amount' => 2500000.00,
        ]);

        expect($account->principal_amount)->toBe('50000000.00')
            ->and($account->interest_rate)->toBe('12.50000')
            ->and($account->outstanding_principal)->toBe('45000000.00')
            ->and($account->outstanding_interest)->toBe('500000.00')
            ->and($account->accrued_interest)->toBe('150000.00')
            ->and($account->total_principal_paid)->toBe('5000000.00')
            ->and($account->total_interest_paid)->toBe('3000000.00')
            ->and($account->total_penalty_paid)->toBe('100000.00')
            ->and($account->ckpn_amount)->toBe('2500000.00');
    });

    it('getOverdueSchedules returns empty collection when none overdue', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'due_date' => now()->addMonths(1),
            'is_paid' => false,
        ]);

        $overdue = $account->getOverdueSchedules();

        expect($overdue)->toBeEmpty();
    });

    it('collaterals relationship returns related collaterals', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        LoanCollateral::create([
            'loan_account_id' => $account->id,
            'collateral_type' => CollateralType::Land,
            'description' => 'Land certificate',
            'appraised_value' => 500000000,
            'liquidation_value' => 350000000,
        ]);

        expect($account->collaterals)->toHaveCount(1);
    });
});

// ============================================================================
// LoanSchedule - Additional Coverage
// ============================================================================
describe('LoanSchedule additional coverage', function (): void {
    it('getRemainingPrincipal returns full amount when nothing paid', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'principal_amount' => 2000000,
            'principal_paid' => 0,
        ]);

        expect($schedule->getRemainingPrincipal())->toBe(2000000.00);
    });

    it('getRemainingInterest returns full amount when nothing paid', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'interest_amount' => 250000,
            'interest_paid' => 0,
        ]);

        expect($schedule->getRemainingInterest())->toBe(250000.00);
    });

    it('getRemainingPrincipal returns zero when fully paid', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $schedule = LoanSchedule::factory()->paid()->create([
            'loan_account_id' => $account->id,
            'principal_amount' => 1500000,
            'principal_paid' => 1500000,
        ]);

        expect($schedule->getRemainingPrincipal())->toBe(0.00);
    });

    it('casts paid_date as date', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $schedule = LoanSchedule::factory()->paid()->create([
            'loan_account_id' => $account->id,
        ]);

        expect($schedule->paid_date)->toBeInstanceOf(Carbon::class)
            ->and($schedule->is_paid)->toBeTrue();
    });

    it('casts outstanding_balance and penalty_paid as decimal', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'outstanding_balance' => 45000000.00,
            'penalty_paid' => 50000.00,
        ]);

        expect($schedule->outstanding_balance)->toBe('45000000.00')
            ->and($schedule->penalty_paid)->toBe('50000.00');
    });
});

// ============================================================================
// LoanPayment - Additional Coverage
// ============================================================================
describe('LoanPayment additional coverage', function (): void {
    it('casts decimal portions correctly', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $payment = LoanPayment::create([
            'reference_number' => 'PAY-CAST-001',
            'loan_account_id' => $account->id,
            'payment_type' => 'installment',
            'amount' => 3000000.00,
            'principal_portion' => 2500000.00,
            'interest_portion' => 400000.00,
            'penalty_portion' => 100000.00,
            'payment_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);

        expect($payment->amount)->toBe('3000000.00')
            ->and($payment->principal_portion)->toBe('2500000.00')
            ->and($payment->interest_portion)->toBe('400000.00')
            ->and($payment->penalty_portion)->toBe('100000.00')
            ->and($payment->payment_date)->toBeInstanceOf(Carbon::class);
    });

    it('journalEntry relationship is BelongsTo', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'PAY-REL-001',
            'loan_account_id' => $account->id,
            'payment_type' => 'installment',
            'amount' => 1000000,
            'principal_portion' => 800000,
            'interest_portion' => 200000,
            'penalty_portion' => 0,
            'payment_date' => now(),
            'performed_by' => $this->user->id,
        ]);

        expect($payment->journalEntry())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// LoanCollateral - Additional Coverage
// ============================================================================
describe('LoanCollateral additional coverage', function (): void {
    it('casts decimal appraised and liquidation values', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $collateral = LoanCollateral::create([
            'loan_application_id' => $app->id,
            'collateral_type' => CollateralType::Vehicle,
            'description' => 'Toyota Avanza 2024',
            'document_number' => 'BPKB/2024/001',
            'appraised_value' => 200000000.00,
            'liquidation_value' => 140000000.00,
            'location' => 'Jakarta',
            'ownership_name' => 'John Doe',
        ]);

        expect($collateral->appraised_value)->toBe('200000000.00')
            ->and($collateral->liquidation_value)->toBe('140000000.00')
            ->and($collateral->collateral_type)->toBe(CollateralType::Vehicle);
    });

    it('loanAccount relationship returns BelongsTo', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $collateral = LoanCollateral::create([
            'loan_application_id' => $app->id,
            'collateral_type' => CollateralType::Land,
            'description' => 'Land',
            'appraised_value' => 500000000,
            'liquidation_value' => 350000000,
        ]);

        expect($collateral->loanAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($collateral->loanApplication())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// LoanApplication - Additional Coverage
// ============================================================================
describe('LoanApplication additional coverage', function (): void {
    it('casts decimal fields and dates correctly', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'requested_amount' => 100000000.00,
            'approved_amount' => 75000000.00,
            'interest_rate' => 12.50000,
            'approved_at' => now(),
            'status' => LoanApplicationStatus::Approved,
        ]);

        expect($app->requested_amount)->toBe('100000000.00')
            ->and($app->approved_amount)->toBe('75000000.00')
            ->and($app->interest_rate)->toBe('12.50000')
            ->and($app->approved_at)->toBeInstanceOf(Carbon::class);
    });

    it('scope pending includes UnderReview', function (): void {
        LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanApplicationStatus::UnderReview,
        ]);
        LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanApplicationStatus::Rejected,
        ]);

        $pending = LoanApplication::pending()->get();

        expect($pending)->toHaveCount(1)
            ->and($pending->first()->status)->toBe(LoanApplicationStatus::UnderReview);
    });

    it('collaterals relationship returns HasMany', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        expect($app->collaterals())->toBeInstanceOf(HasMany::class);
    });
});
