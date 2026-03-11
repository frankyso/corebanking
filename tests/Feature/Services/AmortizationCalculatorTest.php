<?php

use App\Enums\InterestType;
use App\Services\AmortizationCalculator;
use Carbon\Carbon;

describe('AmortizationCalculator', function () {
    beforeEach(function () {
        $this->calculator = app(AmortizationCalculator::class);
        $this->startDate = Carbon::create(2025, 1, 1);
    });

    describe('Flat interest', function () {
        it('produces equal principal payments with fixed interest', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            // All but last installment should have equal principal
            $principalPayments = collect($schedule)->pluck('principal');
            $firstPrincipal = $principalPayments->first();
            $lastPrincipal = $principalPayments->last();

            // First 11 should be equal
            for ($i = 0; $i < 11; $i++) {
                expect($schedule[$i]['principal'])->toBe($firstPrincipal);
            }

            // Interest should be constant across all installments
            $interestPayments = collect($schedule)->pluck('interest')->unique();
            expect($interestPayments->count())->toBe(1);
        });

        it('adjusts last installment for rounding differences', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 10_000_000,
                annualRate: 12,
                tenorMonths: 3,
                startDate: $this->startDate,
            );

            $lastInstallment = end($schedule);

            // Outstanding must be 0 at end
            expect($lastInstallment['outstanding'])->toEqual(0);
        });

        it('has outstanding of 0 at the end', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            $lastInstallment = end($schedule);
            expect($lastInstallment['outstanding'])->toEqual(0);
        });

        it('has correct number of installments', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 6,
                startDate: $this->startDate,
            );

            expect($schedule)->toHaveCount(6);
        });

        it('has correct monthly due dates', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 4,
                startDate: $this->startDate,
            );

            expect($schedule[0]['due_date'])->toBe('2025-02-01')
                ->and($schedule[1]['due_date'])->toBe('2025-03-01')
                ->and($schedule[2]['due_date'])->toBe('2025-04-01')
                ->and($schedule[3]['due_date'])->toBe('2025-05-01');
        });
    });

    describe('Effective interest', function () {
        it('has equal principal payments with declining interest', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Effective,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            // First 11 installments should have equal principal (last adjusts)
            $firstPrincipal = $schedule[0]['principal'];
            for ($i = 1; $i < 11; $i++) {
                expect($schedule[$i]['principal'])->toBe($firstPrincipal);
            }

            // Interest should decline over time
            $interests = collect($schedule)->pluck('interest');
            expect($interests->first())->toBeGreaterThan($interests->last());
        });

        it('first interest is greater than last interest', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Effective,
                principal: 24_000_000,
                annualRate: 18,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            expect($schedule[0]['interest'])->toBeGreaterThan(end($schedule)['interest']);
        });

        it('has outstanding of 0 at the end', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Effective,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            $lastInstallment = end($schedule);
            expect($lastInstallment['outstanding'])->toEqual(0);
        });

        it('has correct number of installments', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Effective,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 6,
                startDate: $this->startDate,
            );

            expect($schedule)->toHaveCount(6);
        });

        it('has correct monthly due dates', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Effective,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 3,
                startDate: $this->startDate,
            );

            expect($schedule[0]['due_date'])->toBe('2025-02-01')
                ->and($schedule[1]['due_date'])->toBe('2025-03-01')
                ->and($schedule[2]['due_date'])->toBe('2025-04-01');
        });
    });

    describe('Annuity interest', function () {
        it('has approximately equal total payments across installments', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            // All but last should have equal total payment
            $totals = collect($schedule)->pluck('total');
            $firstTotal = $totals->first();

            // Check first 11 installments have equal total (within rounding tolerance)
            for ($i = 0; $i < 11; $i++) {
                expect(abs($schedule[$i]['total'] - $firstTotal))->toBeLessThanOrEqual(0.01);
            }
        });

        it('falls back to flat calculation when rate is zero', function () {
            $annuitySchedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 0,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            $flatSchedule = $this->calculator->calculate(
                interestType: InterestType::Flat,
                principal: 12_000_000,
                annualRate: 0,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            expect($annuitySchedule)->toEqual($flatSchedule);
        });

        it('has outstanding of 0 at the end', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            $lastInstallment = end($schedule);
            expect($lastInstallment['outstanding'])->toEqual(0);
        });

        it('has correct number of installments', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 6,
                startDate: $this->startDate,
            );

            expect($schedule)->toHaveCount(6);
        });

        it('has correct monthly due dates', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 3,
                startDate: $this->startDate,
            );

            expect($schedule[0]['due_date'])->toBe('2025-02-01')
                ->and($schedule[1]['due_date'])->toBe('2025-03-01')
                ->and($schedule[2]['due_date'])->toBe('2025-04-01');
        });

        it('has increasing principal and decreasing interest over time', function () {
            $schedule = $this->calculator->calculate(
                interestType: InterestType::Annuity,
                principal: 12_000_000,
                annualRate: 12,
                tenorMonths: 12,
                startDate: $this->startDate,
            );

            // Principal should increase over time
            expect($schedule[0]['principal'])->toBeLessThan($schedule[10]['principal']);

            // Interest should decrease over time
            expect($schedule[0]['interest'])->toBeGreaterThan($schedule[10]['interest']);
        });
    });

    describe('all methods', function () {
        it('each method returns correct installment numbers', function () {
            foreach (InterestType::cases() as $type) {
                $schedule = $this->calculator->calculate(
                    interestType: $type,
                    principal: 12_000_000,
                    annualRate: 12,
                    tenorMonths: 6,
                    startDate: $this->startDate,
                );

                $installmentNumbers = collect($schedule)->pluck('installment')->toArray();
                expect($installmentNumbers)->toBe([1, 2, 3, 4, 5, 6]);
            }
        });

        it('each method has all required keys in each installment', function () {
            foreach (InterestType::cases() as $type) {
                $schedule = $this->calculator->calculate(
                    interestType: $type,
                    principal: 12_000_000,
                    annualRate: 12,
                    tenorMonths: 3,
                    startDate: $this->startDate,
                );

                foreach ($schedule as $installment) {
                    expect($installment)->toHaveKeys([
                        'installment',
                        'due_date',
                        'principal',
                        'interest',
                        'total',
                        'outstanding',
                    ]);
                }
            }
        });

        it('each method sums principal to equal the original principal', function () {
            foreach (InterestType::cases() as $type) {
                $principal = 12_000_000;
                $schedule = $this->calculator->calculate(
                    interestType: $type,
                    principal: $principal,
                    annualRate: 12,
                    tenorMonths: 12,
                    startDate: $this->startDate,
                );

                $totalPrincipal = collect($schedule)->sum('principal');
                expect(round($totalPrincipal, 2))->toBe(round($principal, 2));
            }
        });

        it('each method has total equal to principal plus interest for each row', function () {
            foreach (InterestType::cases() as $type) {
                $schedule = $this->calculator->calculate(
                    interestType: $type,
                    principal: 12_000_000,
                    annualRate: 12,
                    tenorMonths: 6,
                    startDate: $this->startDate,
                );

                foreach ($schedule as $installment) {
                    $expectedTotal = round($installment['principal'] + $installment['interest'], 2);
                    expect($installment['total'])->toBe($expectedTotal);
                }
            }
        });
    });
});
