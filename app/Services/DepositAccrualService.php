<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepositAccrualService
{
    public function accrueDaily(DepositAccount $account, Carbon $date): void
    {
        if ($account->status !== DepositStatus::Active) {
            return;
        }

        $daysInYear = $date->isLeapYear() ? 366 : 365;
        $dailyRate = bcdiv((string) $account->interest_rate, (string) ($daysInYear * 100), 10);
        $accruedAmount = bcmul((string) $account->principal_amount, $dailyRate, 2);
        $taxAmount = $this->calculateTax($account->depositProduct, (float) $accruedAmount);

        $account->interestAccruals()->create([
            'accrual_date' => $date,
            'principal' => $account->principal_amount,
            'interest_rate' => $account->interest_rate,
            'accrued_amount' => $accruedAmount,
            'tax_amount' => $taxAmount,
            'is_posted' => false,
        ]);

        $account->update([
            'accrued_interest' => bcadd($account->accrued_interest, $accruedAmount, 2),
        ]);
    }

    public function payMonthlyInterest(DepositAccount $account, User $performer): void
    {
        if ($account->interest_payment_method !== InterestPaymentMethod::Monthly) {
            return;
        }

        if ($account->status !== DepositStatus::Active) {
            return;
        }

        DB::transaction(function () use ($account, $performer): void {
            $accruedInterest = (float) $account->accrued_interest;
            if ($accruedInterest <= 0) {
                return;
            }

            $taxAmount = $this->calculateTax($account->depositProduct, $accruedInterest);
            $netInterest = bcsub((string) $accruedInterest, (string) $taxAmount, 2);

            $this->createTransaction(
                account: $account,
                type: 'interest_payment',
                amount: (float) $netInterest,
                performer: $performer,
                description: 'Pembayaran bunga bulanan',
            );

            if ($taxAmount > 0) {
                $this->createTransaction(
                    account: $account,
                    type: 'tax',
                    amount: $taxAmount,
                    performer: $performer,
                    description: 'Pajak bunga deposito',
                );
            }

            $account->update([
                'accrued_interest' => 0,
                'total_interest_paid' => bcadd($account->total_interest_paid, $netInterest, 2),
                'total_tax_paid' => bcadd($account->total_tax_paid, (string) $taxAmount, 2),
                'last_interest_paid_at' => now(),
            ]);

            $account->interestAccruals()
                ->where('is_posted', false)
                ->update(['is_posted' => true, 'posted_at' => now()]);
        });
    }

    protected function createTransaction(
        DepositAccount $account,
        string $type,
        float $amount,
        User $performer,
        ?string $description = null,
    ): DepositTransaction {
        return DepositTransaction::create([
            'reference_number' => $this->generateTransactionReference(),
            'deposit_account_id' => $account->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'performed_by' => $performer->id,
        ]);
    }

    protected function generateTransactionReference(): string
    {
        return 'DEP'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function calculateTax(DepositProduct $product, float $interestAmount): float
    {
        $taxRate = (float) $product->tax_rate;
        if ($taxRate <= 0) {
            return 0;
        }

        return (float) bcmul((string) $interestAmount, bcdiv((string) $taxRate, '100', 8), 2);
    }
}
