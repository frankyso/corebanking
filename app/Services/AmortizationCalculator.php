<?php

namespace App\Services;

use App\Enums\InterestType;
use Carbon\Carbon;

class AmortizationCalculator
{
    /**
     * @return array<int, array{installment: int, due_date: string, principal: float, interest: float, total: float, outstanding: float}>
     */
    public function calculate(
        InterestType $interestType,
        float $principal,
        float $annualRate,
        int $tenorMonths,
        Carbon $startDate,
    ): array {
        return match ($interestType) {
            InterestType::Flat => $this->calculateFlat($principal, $annualRate, $tenorMonths, $startDate),
            InterestType::Effective => $this->calculateEffective($principal, $annualRate, $tenorMonths, $startDate),
            InterestType::Annuity => $this->calculateAnnuity($principal, $annualRate, $tenorMonths, $startDate),
        };
    }

    /**
     * @return array<int, array{installment: int, due_date: string, principal: float, interest: float, total: float, outstanding: float}>
     */
    protected function calculateFlat(float $principal, float $annualRate, int $tenorMonths, Carbon $startDate): array
    {
        $monthlyPrincipal = bcdiv((string) $principal, (string) $tenorMonths, 2);
        $totalInterest = bcmul((string) $principal, bcdiv((string) $annualRate, '100', 8), 2);
        $totalInterest = bcmul($totalInterest, bcdiv((string) $tenorMonths, '12', 8), 2);
        $monthlyInterest = bcdiv($totalInterest, (string) $tenorMonths, 2);

        $schedule = [];
        $outstanding = $principal;

        for ($i = 1; $i <= $tenorMonths; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);

            if ($i === $tenorMonths) {
                $monthlyPrincipal = $outstanding;
            }

            $outstanding = (float) bcsub((string) $outstanding, $monthlyPrincipal, 2);

            $schedule[] = [
                'installment' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal' => (float) $monthlyPrincipal,
                'interest' => (float) $monthlyInterest,
                'total' => (float) bcadd($monthlyPrincipal, $monthlyInterest, 2),
                'outstanding' => max(0, $outstanding),
            ];
        }

        return $schedule;
    }

    /**
     * @return array<int, array{installment: int, due_date: string, principal: float, interest: float, total: float, outstanding: float}>
     */
    protected function calculateEffective(float $principal, float $annualRate, int $tenorMonths, Carbon $startDate): array
    {
        $monthlyPrincipal = bcdiv((string) $principal, (string) $tenorMonths, 2);
        $monthlyRate = bcdiv((string) $annualRate, '1200', 10);

        $schedule = [];
        $outstanding = $principal;

        for ($i = 1; $i <= $tenorMonths; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);
            $interest = bcmul((string) $outstanding, $monthlyRate, 2);

            if ($i === $tenorMonths) {
                $monthlyPrincipal = (string) $outstanding;
            }

            $outstanding = (float) bcsub((string) $outstanding, $monthlyPrincipal, 2);

            $schedule[] = [
                'installment' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal' => (float) $monthlyPrincipal,
                'interest' => (float) $interest,
                'total' => (float) bcadd($monthlyPrincipal, $interest, 2),
                'outstanding' => max(0, $outstanding),
            ];
        }

        return $schedule;
    }

    /**
     * @return array<int, array{installment: int, due_date: string, principal: float, interest: float, total: float, outstanding: float}>
     */
    protected function calculateAnnuity(float $principal, float $annualRate, int $tenorMonths, Carbon $startDate): array
    {
        $monthlyRate = (float) bcdiv((string) $annualRate, '1200', 10);

        if ($monthlyRate == 0) {
            return $this->calculateFlat($principal, $annualRate, $tenorMonths, $startDate);
        }

        $annuityFactor = ($monthlyRate * pow(1 + $monthlyRate, $tenorMonths)) / (pow(1 + $monthlyRate, $tenorMonths) - 1);
        $monthlyPayment = round($principal * $annuityFactor, 2);

        $schedule = [];
        $outstanding = $principal;

        for ($i = 1; $i <= $tenorMonths; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);
            $interest = round($outstanding * $monthlyRate, 2);
            $principalPortion = round($monthlyPayment - $interest, 2);

            if ($i === $tenorMonths) {
                $principalPortion = $outstanding;
                $monthlyPayment = $principalPortion + $interest;
            }

            $outstanding = max(0, round($outstanding - $principalPortion, 2));

            $schedule[] = [
                'installment' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal' => $principalPortion,
                'interest' => $interest,
                'total' => round($principalPortion + $interest, 2),
                'outstanding' => $outstanding,
            ];
        }

        return $schedule;
    }
}
