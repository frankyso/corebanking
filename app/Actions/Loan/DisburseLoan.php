<?php

namespace App\Actions\Loan;

use App\Enums\Collectibility;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\AmortizationCalculator;
use App\Services\SequenceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DisburseLoan
{
    public function __construct(
        private SequenceService $sequenceService,
        private AmortizationCalculator $amortizationCalculator,
    ) {}

    public function execute(LoanApplication $application, User $performer, ?Carbon $disbursementDate = null): LoanAccount
    {
        if ($application->status !== LoanApplicationStatus::Approved) {
            throw InvalidLoanStatusException::notApproved($application);
        }

        return DB::transaction(function () use ($application, $performer, $disbursementDate): LoanAccount {
            $product = $application->loanProduct;
            $branchCode = $performer->branch?->code ?? '001';
            $accountNumber = $this->sequenceService->generateAccountNumber($product->code, $branchCode);
            $disbDate = $disbursementDate ?? now();
            $amount = (float) $application->approved_amount;
            $tenor = $application->approved_tenor_months;
            $maturityDate = $disbDate->copy()->addMonths($tenor);

            $account = LoanAccount::create([
                'account_number' => $accountNumber,
                'customer_id' => $application->customer_id,
                'loan_product_id' => $product->id,
                'loan_application_id' => $application->id,
                'branch_id' => $application->branch_id,
                'status' => LoanStatus::Active,
                'principal_amount' => $amount,
                'interest_rate' => $application->interest_rate,
                'tenor_months' => $tenor,
                'outstanding_principal' => $amount,
                'outstanding_interest' => 0,
                'accrued_interest' => 0,
                'total_principal_paid' => 0,
                'total_interest_paid' => 0,
                'total_penalty_paid' => 0,
                'disbursement_date' => $disbDate,
                'maturity_date' => $maturityDate,
                'dpd' => 0,
                'collectibility' => Collectibility::Current,
                'ckpn_amount' => 0,
                'loan_officer_id' => $application->loan_officer_id,
                'created_by' => $performer->id,
            ]);

            $schedule = $this->amortizationCalculator->calculate(
                interestType: $product->interest_type,
                principal: $amount,
                annualRate: (float) $application->interest_rate,
                tenorMonths: $tenor,
                startDate: $disbDate,
            );

            foreach ($schedule as $item) {
                $account->schedules()->create([
                    'installment_number' => $item['installment'],
                    'due_date' => $item['due_date'],
                    'principal_amount' => $item['principal'],
                    'interest_amount' => $item['interest'],
                    'total_amount' => $item['total'],
                    'outstanding_balance' => $item['outstanding'],
                ]);
            }

            $application->update([
                'status' => LoanApplicationStatus::Disbursed,
                'disbursed_at' => now(),
            ]);

            foreach ($application->collaterals as $collateral) {
                $collateral->update(['loan_account_id' => $account->id]);
            }

            return $account;
        });
    }
}
