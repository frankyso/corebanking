<?php

namespace App\Services;

use App\Enums\InterestCalcMethod;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use Carbon\Carbon;

class SavingsInterestCalculator
{
    public function calculateDailyAccrual(SavingsAccount $account, Carbon $date): SavingsInterestAccrual
    {
        $product = $account->savingsProduct;
        $balance = $account->balance;
        $annualRate = (float) $product->interest_rate;
        $daysInYear = $date->isLeapYear() ? 366 : 365;

        $dailyInterest = bcmul(
            bcdiv((string) $balance, (string) $daysInYear, 10),
            bcdiv((string) $annualRate, '100', 10),
            2
        );

        $taxAmount = $this->calculateTax($balance, (float) $dailyInterest, $product);

        return $account->interestAccruals()->create([
            'accrual_date' => $date,
            'balance' => $balance,
            'interest_rate' => $annualRate,
            'accrued_amount' => $dailyInterest,
            'tax_amount' => $taxAmount,
        ]);
    }

    public function calculateMonthlyInterest(SavingsAccount $account, Carbon $month): array
    {
        $product = $account->savingsProduct;
        $method = $product->interest_calc_method;

        $balance = match ($method) {
            InterestCalcMethod::DailyBalance => $this->getDailyBalanceAverage($account, $month),
            InterestCalcMethod::AverageBalance => $this->getAverageBalance($account, $month),
            InterestCalcMethod::LowestBalance => $this->getLowestBalance($account, $month),
        };

        $annualRate = (float) $product->interest_rate;
        $daysInMonth = $month->daysInMonth;
        $daysInYear = $month->isLeapYear() ? 366 : 365;

        $interest = bcmul(
            bcmul($balance, bcdiv((string) $annualRate, '100', 10), 10),
            bcdiv((string) $daysInMonth, (string) $daysInYear, 10),
            2
        );

        $tax = $this->calculateTax($balance, (float) $interest, $product);

        return [
            'balance' => $balance,
            'interest' => $interest,
            'tax' => $tax,
            'net_interest' => bcsub($interest, $tax, 2),
        ];
    }

    protected function getDailyBalanceAverage(SavingsAccount $account, Carbon $month): string
    {
        $accruals = $account->interestAccruals()
            ->whereMonth('accrual_date', $month->month)
            ->whereYear('accrual_date', $month->year)
            ->get();

        if ($accruals->isEmpty()) {
            return (string) $account->balance;
        }

        $totalBalance = $accruals->sum('balance');

        return bcdiv((string) $totalBalance, (string) $accruals->count(), 2);
    }

    protected function getAverageBalance(SavingsAccount $account, Carbon $month): string
    {
        return $this->getDailyBalanceAverage($account, $month);
    }

    protected function getLowestBalance(SavingsAccount $account, Carbon $month): string
    {
        $accruals = $account->interestAccruals()
            ->whereMonth('accrual_date', $month->month)
            ->whereYear('accrual_date', $month->year)
            ->get();

        if ($accruals->isEmpty()) {
            return (string) $account->balance;
        }

        return (string) $accruals->min('balance');
    }

    protected function calculateTax(float $balance, float $interest, $product): string
    {
        if ($balance < (float) $product->tax_threshold) {
            return '0.00';
        }

        return bcmul((string) $interest, bcdiv((string) $product->tax_rate, '100', 10), 2);
    }
}
