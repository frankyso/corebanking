<?php

namespace App\Actions\Loan;

use App\DTOs\Loan\MakeLoanPaymentData;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use Illuminate\Support\Facades\DB;

class MakeLoanPayment
{
    public function execute(MakeLoanPaymentData $dto): LoanPayment
    {
        if (! in_array($dto->account->status, [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])) {
            throw InvalidLoanStatusException::notActive($dto->account);
        }

        if ($dto->amount <= 0) {
            throw InvalidLoanStatusException::invalidPaymentAmount($dto->amount);
        }

        return DB::transaction(function () use ($dto): LoanPayment {
            $remaining = $dto->amount;
            $penaltyPortion = 0;
            $interestPortion = 0;
            $principalPortion = 0;

            $overdueSchedules = $dto->account->getOverdueSchedules();
            foreach ($overdueSchedules as $schedule) {
                if ($remaining <= 0) {
                    break;
                }

                $interestDue = $schedule->getRemainingInterest();
                if ($interestDue > 0 && $remaining > 0) {
                    $paid = min($remaining, $interestDue);
                    $schedule->increment('interest_paid', $paid);
                    $interestPortion = bcadd((string) $interestPortion, (string) $paid, 2);
                    $remaining = bcsub((string) $remaining, (string) $paid, 2);
                }

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

            if ((float) $remaining > 0) {
                $currentSchedule = $dto->account->getNextUnpaidSchedule();
                if ($currentSchedule instanceof LoanSchedule) {
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
                'loan_account_id' => $dto->account->id,
                'payment_type' => 'installment',
                'amount' => $dto->amount,
                'principal_portion' => $principalPortion,
                'interest_portion' => $interestPortion,
                'penalty_portion' => $penaltyPortion,
                'payment_date' => now()->toDateString(),
                'description' => $dto->description ?? 'Pembayaran angsuran',
                'performed_by' => $dto->performer->id,
            ]);

            $dto->account->update([
                'outstanding_principal' => bcsub((string) $dto->account->outstanding_principal, (string) $principalPortion, 2),
                'total_principal_paid' => bcadd((string) $dto->account->total_principal_paid, (string) $principalPortion, 2),
                'total_interest_paid' => bcadd((string) $dto->account->total_interest_paid, (string) $interestPortion, 2),
                'total_penalty_paid' => bcadd((string) $dto->account->total_penalty_paid, (string) $penaltyPortion, 2),
                'last_payment_date' => now(),
            ]);

            if ((float) $dto->account->fresh()->outstanding_principal <= 0) {
                $dto->account->update(['status' => LoanStatus::Closed]);
            }

            return $payment;
        });
    }

    private function generatePaymentReference(): string
    {
        return 'PAY'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
