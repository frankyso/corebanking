<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\Actions\MobileBanking\PayLoanFromSavings;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\InterestType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\MobileUser;
use App\Models\SavingsProduct;
use App\Models\User;
use Carbon\Carbon;

describe('PayLoanFromSavings', function (): void {
    beforeEach(function (): void {
        $this->action = app(PayLoanFromSavings::class);

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

        // Create MOBILE_SYSTEM user (required by PayLoanFromSavings)
        User::firstOrCreate(
            ['employee_id' => 'MOBILE_SYSTEM'],
            [
                'name' => 'Mobile Banking System',
                'email' => 'mobile-system@corebanking.test',
                'password' => bcrypt('secret'),
                'is_active' => true,
                'branch_id' => $this->branch->id,
            ],
        );

        // Savings setup
        $this->savingsProduct = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'max_balance' => null,
            'closing_fee' => 25000,
        ]);

        $this->savingsAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->savingsProduct,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 5000000,
            performer: $this->user,
        ));

        // Loan setup
        $this->loanProduct = LoanProduct::factory()->create([
            'code' => 'KMK',
            'interest_type' => InterestType::Annuity,
            'interest_rate' => 12.00,
            'min_amount' => 1000000,
            'max_amount' => 500000000,
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
        ]);

        $application = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->loanProduct,
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

        $this->mobileUser = MobileUser::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
    });

    it('successfully pays loan from savings account', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
        $paymentAmount = (float) $firstSchedule->total_amount;

        $originalSavingsBalance = (float) $this->savingsAccount->balance;
        $originalOutstanding = (float) $this->loanAccount->outstanding_principal;

        $payment = $this->action->execute(
            savingsAccount: $this->savingsAccount,
            loanAccount: $this->loanAccount,
            amount: $paymentAmount,
            performer: $this->mobileUser,
        );

        $this->savingsAccount->refresh();
        $this->loanAccount->refresh();

        expect($payment)->toBeInstanceOf(LoanPayment::class)
            ->and((float) $this->savingsAccount->balance)->toBeLessThan($originalSavingsBalance)
            ->and((float) $this->loanAccount->outstanding_principal)->toBeLessThan($originalOutstanding)
            ->and((float) $payment->amount)->toBe($paymentAmount)
            ->and((float) $payment->principal_portion)->toBeGreaterThan(0)
            ->and((float) $payment->interest_portion)->toBeGreaterThan(0);
    });

    it('decreases savings balance by the payment amount', function (): void {
        $paymentAmount = 500000;
        $originalBalance = (float) $this->savingsAccount->balance;

        $this->action->execute(
            savingsAccount: $this->savingsAccount,
            loanAccount: $this->loanAccount,
            amount: $paymentAmount,
            performer: $this->mobileUser,
        );

        $this->savingsAccount->refresh();

        expect((float) $this->savingsAccount->balance)->toBe($originalBalance - $paymentAmount);
    });

    it('marks loan schedule as paid when fully covered', function (): void {
        $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
        $paymentAmount = (float) $firstSchedule->total_amount;

        $this->action->execute(
            savingsAccount: $this->savingsAccount,
            loanAccount: $this->loanAccount,
            amount: $paymentAmount,
            performer: $this->mobileUser,
        );

        $firstSchedule->refresh();

        expect($firstSchedule->is_paid)->toBeTrue()
            ->and($firstSchedule->paid_date)->not->toBeNull();
    });

    it('throws exception for insufficient savings balance', function (): void {
        // Savings balance = 5,000,000 but loan total is 12,000,000+
        $totalDue = $this->loanAccount->schedules()->sum('total_amount');

        expect(fn () => $this->action->execute(
            savingsAccount: $this->savingsAccount,
            loanAccount: $this->loanAccount,
            amount: (float) $totalDue,
            performer: $this->mobileUser,
        ))->toThrow(InsufficientSavingsBalanceException::class);
    });

    it('throws exception when remaining savings balance would be below minimum', function (): void {
        // Savings balance = 5,000,000, min_balance = 25,000
        // Attempt to withdraw 4,980,000 => remaining = 20,000 < 25,000
        expect(fn () => $this->action->execute(
            savingsAccount: $this->savingsAccount,
            loanAccount: $this->loanAccount,
            amount: 4980000,
            performer: $this->mobileUser,
        ))->toThrow(SavingsBalanceLimitException::class);
    });
});
