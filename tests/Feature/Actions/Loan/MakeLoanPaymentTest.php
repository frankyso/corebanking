<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\Actions\Loan\MakeLoanPayment;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\DTOs\Loan\MakeLoanPaymentData;
use App\Enums\InterestType;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;
use Carbon\Carbon;

describe('MakeLoanPayment', function (): void {
    beforeEach(function (): void {
        $this->action = app(MakeLoanPayment::class);

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

        $application = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 12000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));

        app(ApproveLoanApplication::class)->execute(new ApproveLoanApplicationData(
            application: $application,
            approver: $this->approver,
        ));
        $application->refresh();

        $this->loanAccount = app(DisburseLoan::class)->execute(
            $application,
            $this->user,
            Carbon::parse('2025-01-15'),
        );
    });

    it('creates a LoanPayment record with correct portions', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
        $paymentAmount = (float) $firstSchedule->total_amount;

        $payment = $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: $paymentAmount,
            performer: $this->user,
        ));

        expect($payment->loan_account_id)->toBe($this->loanAccount->id)
            ->and($payment->reference_number)->toStartWith('PAY')
            ->and((float) $payment->amount)->toBe($paymentAmount)
            ->and((float) $payment->interest_portion)->toBeGreaterThan(0)
            ->and((float) $payment->principal_portion)->toBeGreaterThan(0);
    });

    it('updates loan account outstanding principal', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
        $originalPrincipal = (float) $this->loanAccount->outstanding_principal;

        $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: (float) $firstSchedule->total_amount,
            performer: $this->user,
        ));

        $this->loanAccount->refresh();
        expect((float) $this->loanAccount->outstanding_principal)->toBeLessThan($originalPrincipal)
            ->and((float) $this->loanAccount->total_principal_paid)->toBeGreaterThan(0)
            ->and((float) $this->loanAccount->total_interest_paid)->toBeGreaterThan(0);
    });

    it('marks schedule as paid when fully covered', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();

        $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: (float) $firstSchedule->total_amount,
            performer: $this->user,
        ));

        $firstSchedule->refresh();
        expect($firstSchedule->is_paid)->toBeTrue()
            ->and($firstSchedule->paid_date)->not->toBeNull();
    });

    it('handles partial payment that covers only interest', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
        $interestOnly = (float) $firstSchedule->interest_amount;

        $payment = $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: $interestOnly,
            performer: $this->user,
        ));

        expect((float) $payment->interest_portion)->toBe($interestOnly)
            ->and((float) $payment->principal_portion)->toBe(0.00);

        $firstSchedule->refresh();
        expect($firstSchedule->is_paid)->toBeFalse();
    });

    it('closes loan when outstanding principal reaches zero', function (): void {
        $schedules = $this->loanAccount->schedules()->orderBy('installment_number')->get();
        $totalDue = $schedules->sum(fn ($s): float => (float) $s->total_amount);

        $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: $totalDue,
            performer: $this->user,
        ));

        expect($this->loanAccount->fresh()->status)->toBe(LoanStatus::Closed);
    });

    it('throws when loan is not in active status', function (): void {
        $this->loanAccount->update(['status' => LoanStatus::Closed]);

        $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: 1000000,
            performer: $this->user,
        ));
    })->throws(InvalidLoanStatusException::class, 'Pinjaman tidak dalam status aktif');

    it('throws when payment amount is zero or negative', function (): void {
        $this->action->execute(new MakeLoanPaymentData(
            account: $this->loanAccount,
            amount: 0,
            performer: $this->user,
        ));
    })->throws(InvalidLoanStatusException::class, 'Jumlah pembayaran harus lebih dari 0');
});
