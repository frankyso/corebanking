<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Enums\CollateralType;
use App\Enums\InterestType;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanCollateral;
use App\Models\LoanProduct;
use App\Models\User;
use Carbon\Carbon;

describe('DisburseLoan', function (): void {
    beforeEach(function (): void {
        $this->action = app(DisburseLoan::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = LoanProduct::factory()->create([
            'code' => 'KMK',
            'interest_type' => InterestType::Annuity,
            'interest_rate' => 12.00,
            'min_amount' => 1000000,
            'max_amount' => 500000000,
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
        ]);

        $this->application = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 10000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));

        app(ApproveLoanApplication::class)->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->approver,
        ));
        $this->application->refresh();
    });

    it('creates a loan account from an approved application', function (): void {
        $account = $this->action->execute($this->application, $this->user);

        expect($account)->toBeInstanceOf(LoanAccount::class)
            ->and($account->account_number)->not->toBeNull()
            ->and($account->status)->toBe(LoanStatus::Active)
            ->and((float) $account->principal_amount)->toBe(10000000.00)
            ->and((float) $account->outstanding_principal)->toBe(10000000.00)
            ->and((float) $account->interest_rate)->toBe(12.00)
            ->and($account->tenor_months)->toBe(12)
            ->and($account->customer_id)->toBe($this->customer->id);
    });

    it('generates amortization schedule with correct number of installments', function (): void {
        $account = $this->action->execute($this->application, $this->user);

        expect($account->schedules()->count())->toBe(12);

        $firstSchedule = $account->schedules()->orderBy('installment_number')->first();
        expect($firstSchedule->installment_number)->toBe(1)
            ->and((float) $firstSchedule->total_amount)->toBeGreaterThan(0);
    });

    it('updates application status to Disbursed', function (): void {
        $this->action->execute($this->application, $this->user);

        expect($this->application->fresh()->status)->toBe(LoanApplicationStatus::Disbursed);
    });

    it('calculates maturity date correctly', function (): void {
        $disbDate = Carbon::parse('2026-01-15');
        $account = $this->action->execute($this->application, $this->user, $disbDate);

        expect($account->disbursement_date->format('Y-m-d'))->toBe('2026-01-15')
            ->and($account->maturity_date->format('Y-m-d'))->toBe('2027-01-15');
    });

    it('copies collaterals from application to account', function (): void {
        LoanCollateral::create([
            'loan_application_id' => $this->application->id,
            'collateral_type' => CollateralType::Land,
            'description' => 'Tanah 100m2',
            'appraised_value' => 200000000,
            'liquidation_value' => 150000000,
        ]);

        $account = $this->action->execute($this->application, $this->user);

        expect($account->collaterals()->count())->toBe(1);
    });

    it('throws when application is not approved', function (): void {
        $newApp = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 5000000,
            requestedTenor: 6,
            purpose: 'Test',
            creator: $this->user,
        ));

        $this->action->execute($newApp, $this->user);
    })->throws(InvalidLoanStatusException::class, 'Permohonan belum disetujui');
});
