<?php

namespace App\Services;

use App\Enums\Collectibility;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(
        private SequenceService $sequenceService,
        private AmortizationCalculator $amortizationCalculator,
    ) {}

    public function createApplication(
        LoanProduct $product,
        int $customerId,
        int $branchId,
        float $requestedAmount,
        int $requestedTenor,
        string $purpose,
        User $creator,
        ?int $loanOfficerId = null,
    ): LoanApplication {
        if ($requestedAmount < (float) $product->min_amount) {
            throw new \InvalidArgumentException('Jumlah pinjaman kurang dari minimum');
        }

        if ($product->max_amount && $requestedAmount > (float) $product->max_amount) {
            throw new \InvalidArgumentException('Jumlah pinjaman melebihi maksimum');
        }

        if ($requestedTenor < $product->min_tenor_months || $requestedTenor > $product->max_tenor_months) {
            throw new \InvalidArgumentException("Tenor harus antara {$product->min_tenor_months} - {$product->max_tenor_months} bulan");
        }

        return DB::transaction(function () use ($product, $customerId, $branchId, $requestedAmount, $requestedTenor, $purpose, $creator, $loanOfficerId) {
            $applicationNumber = $this->generateApplicationNumber();

            return LoanApplication::create([
                'application_number' => $applicationNumber,
                'customer_id' => $customerId,
                'loan_product_id' => $product->id,
                'branch_id' => $branchId,
                'status' => LoanApplicationStatus::Submitted,
                'requested_amount' => $requestedAmount,
                'requested_tenor_months' => $requestedTenor,
                'interest_rate' => $product->interest_rate,
                'purpose' => $purpose,
                'loan_officer_id' => $loanOfficerId,
                'created_by' => $creator->id,
            ]);
        });
    }

    public function approveApplication(LoanApplication $application, User $approver, ?float $approvedAmount = null, ?int $approvedTenor = null): LoanApplication
    {
        if (! in_array($application->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])) {
            throw new \InvalidArgumentException('Permohonan tidak dalam status yang dapat disetujui');
        }

        if ($application->created_by === $approver->id) {
            throw new \InvalidArgumentException('Tidak dapat menyetujui permohonan yang Anda buat sendiri');
        }

        $application->update([
            'status' => LoanApplicationStatus::Approved,
            'approved_amount' => $approvedAmount ?? $application->requested_amount,
            'approved_tenor_months' => $approvedTenor ?? $application->requested_tenor_months,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $application->fresh();
    }

    public function rejectApplication(LoanApplication $application, User $approver, string $reason): LoanApplication
    {
        if (! in_array($application->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])) {
            throw new \InvalidArgumentException('Permohonan tidak dalam status yang dapat ditolak');
        }

        $application->update([
            'status' => LoanApplicationStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $application->fresh();
    }

    public function disburse(LoanApplication $application, User $performer, ?Carbon $disbursementDate = null): LoanAccount
    {
        if ($application->status !== LoanApplicationStatus::Approved) {
            throw new \InvalidArgumentException('Permohonan belum disetujui');
        }

        return DB::transaction(function () use ($application, $performer, $disbursementDate) {
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

            // Copy collaterals from application to account
            foreach ($application->collaterals as $collateral) {
                $collateral->update(['loan_account_id' => $account->id]);
            }

            return $account;
        });
    }

    public function makePayment(LoanAccount $account, float $amount, User $performer, ?string $description = null): LoanPayment
    {
        if (! in_array($account->status, [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])) {
            throw new \InvalidArgumentException('Pinjaman tidak dalam status aktif');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah pembayaran harus lebih dari 0');
        }

        return DB::transaction(function () use ($account, $amount, $performer, $description) {
            $remaining = $amount;
            $penaltyPortion = 0;
            $interestPortion = 0;
            $principalPortion = 0;

            // Allocation: penalty → overdue interest → overdue principal → current interest → current principal
            $overdueSchedules = $account->getOverdueSchedules();
            foreach ($overdueSchedules as $schedule) {
                if ($remaining <= 0) {
                    break;
                }

                // Interest first
                $interestDue = $schedule->getRemainingInterest();
                if ($interestDue > 0 && $remaining > 0) {
                    $paid = min($remaining, $interestDue);
                    $schedule->increment('interest_paid', $paid);
                    $interestPortion = bcadd((string) $interestPortion, (string) $paid, 2);
                    $remaining = bcsub((string) $remaining, (string) $paid, 2);
                }

                // Then principal
                $principalDue = $schedule->getRemainingPrincipal();
                if ($principalDue > 0 && $remaining > 0) {
                    $paid = min($remaining, $principalDue);
                    $schedule->increment('principal_paid', $paid);
                    $principalPortion = bcadd((string) $principalPortion, (string) $paid, 2);
                    $remaining = bcsub((string) $remaining, (string) $paid, 2);
                }

                $schedule->refresh();
                if ($schedule->getRemainingPrincipal() <= 0 && $schedule->getRemainingInterest() <= 0) {
                    $schedule->update(['is_paid' => true, 'paid_date' => now()]);
                }
            }

            // Current installment
            if ((float) $remaining > 0) {
                $currentSchedule = $account->getNextUnpaidSchedule();
                if ($currentSchedule) {
                    $interestDue = $currentSchedule->getRemainingInterest();
                    if ($interestDue > 0 && (float) $remaining > 0) {
                        $paid = min((float) $remaining, $interestDue);
                        $currentSchedule->increment('interest_paid', $paid);
                        $interestPortion = bcadd((string) $interestPortion, (string) $paid, 2);
                        $remaining = bcsub((string) $remaining, (string) $paid, 2);
                    }

                    $principalDue = $currentSchedule->getRemainingPrincipal();
                    if ($principalDue > 0 && (float) $remaining > 0) {
                        $paid = min((float) $remaining, $principalDue);
                        $currentSchedule->increment('principal_paid', $paid);
                        $principalPortion = bcadd((string) $principalPortion, (string) $paid, 2);
                        $remaining = bcsub((string) $remaining, (string) $paid, 2);
                    }

                    $currentSchedule->refresh();
                    if ($currentSchedule->getRemainingPrincipal() <= 0 && $currentSchedule->getRemainingInterest() <= 0) {
                        $currentSchedule->update(['is_paid' => true, 'paid_date' => now()]);
                    }
                }
            }

            $payment = LoanPayment::create([
                'reference_number' => $this->generatePaymentReference(),
                'loan_account_id' => $account->id,
                'payment_type' => 'installment',
                'amount' => $amount,
                'principal_portion' => $principalPortion,
                'interest_portion' => $interestPortion,
                'penalty_portion' => $penaltyPortion,
                'payment_date' => now()->toDateString(),
                'description' => $description ?? 'Pembayaran angsuran',
                'performed_by' => $performer->id,
            ]);

            $account->update([
                'outstanding_principal' => bcsub((string) $account->outstanding_principal, (string) $principalPortion, 2),
                'total_principal_paid' => bcadd((string) $account->total_principal_paid, (string) $principalPortion, 2),
                'total_interest_paid' => bcadd((string) $account->total_interest_paid, (string) $interestPortion, 2),
                'total_penalty_paid' => bcadd((string) $account->total_penalty_paid, (string) $penaltyPortion, 2),
                'last_payment_date' => now(),
            ]);

            if ((float) $account->fresh()->outstanding_principal <= 0) {
                $account->update(['status' => LoanStatus::Closed]);
            }

            return $payment;
        });
    }

    public function updateDpd(LoanAccount $account): void
    {
        $oldestOverdue = $account->schedules()
            ->where('is_paid', false)
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->first();

        if (! $oldestOverdue) {
            $account->update(['dpd' => 0, 'status' => LoanStatus::Current]);

            return;
        }

        $dpd = (int) $oldestOverdue->due_date->diffInDays(now());
        $account->update([
            'dpd' => $dpd,
            'status' => $dpd > 0 ? LoanStatus::Overdue : LoanStatus::Current,
        ]);
    }

    public function updateCollectibility(LoanAccount $account): void
    {
        $dpd = $account->dpd;

        $collectibility = match (true) {
            $dpd <= 0 => Collectibility::Current,
            $dpd <= 90 => Collectibility::SpecialMention,
            $dpd <= 120 => Collectibility::Substandard,
            $dpd <= 180 => Collectibility::Doubtful,
            default => Collectibility::Loss,
        };

        $ckpn = bcmul((string) $account->outstanding_principal, (string) $collectibility->ckpnRate(), 2);

        $account->update([
            'collectibility' => $collectibility,
            'ckpn_amount' => $ckpn,
        ]);
    }

    protected function generateApplicationNumber(): string
    {
        return 'APP'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function generatePaymentReference(): string
    {
        return 'PAY'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
