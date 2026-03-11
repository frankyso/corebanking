<?php

use App\Models\Sequence;
use App\Services\SequenceService;

describe('SequenceService', function () {
    beforeEach(function () {
        $this->service = app(SequenceService::class);
    });

    describe('generateCifNumber', function () {
        it('generates correct format: branchCode + 2-digit year + 7-digit padded number', function () {
            $cif = $this->service->generateCifNumber('001');

            $year = date('y');
            expect($cif)
                ->toStartWith("001{$year}")
                ->toHaveLength(12) // 3 (branch) + 2 (year) + 7 (padded)
                ->and(substr($cif, 5))
                ->toBe('0000001');
        });

        it('increments the number on sequential calls', function () {
            $cif1 = $this->service->generateCifNumber('001');
            $cif2 = $this->service->generateCifNumber('001');

            $number1 = (int) substr($cif1, 5);
            $number2 = (int) substr($cif2, 5);

            expect($number2)->toBe($number1 + 1);
        });

        it('maintains separate sequences for different branch codes', function () {
            $cifA = $this->service->generateCifNumber('001');
            $cifB = $this->service->generateCifNumber('002');

            expect(substr($cifA, 5))->toBe('0000001')
                ->and(substr($cifB, 5))->toBe('0000001');
        });
    });

    describe('generateAccountNumber', function () {
        it('generates correct format: productCode + branchCode + 9-digit padded number', function () {
            $accountNumber = $this->service->generateAccountNumber('T01', '001');

            expect($accountNumber)
                ->toStartWith('T01001')
                ->toHaveLength(15) // 3 (product) + 3 (branch) + 9 (padded)
                ->and(substr($accountNumber, 6))
                ->toBe('000000001');
        });

        it('increments the number on sequential calls', function () {
            $acc1 = $this->service->generateAccountNumber('T01', '001');
            $acc2 = $this->service->generateAccountNumber('T01', '001');

            $number1 = (int) substr($acc1, 6);
            $number2 = (int) substr($acc2, 6);

            expect($number2)->toBe($number1 + 1);
        });
    });

    describe('generateJournalNumber', function () {
        it('generates correct format: JRN + date + 5-digit padded number', function () {
            $journal = $this->service->generateJournalNumber();

            $dateStr = date('Ymd');
            expect($journal)
                ->toStartWith("JRN{$dateStr}")
                ->toHaveLength(16) // 3 (JRN) + 8 (date) + 5 (padded)
                ->and(substr($journal, 11))
                ->toBe('00001');
        });

        it('increments the number on sequential calls', function () {
            $jrn1 = $this->service->generateJournalNumber();
            $jrn2 = $this->service->generateJournalNumber();

            $number1 = (int) substr($jrn1, 11);
            $number2 = (int) substr($jrn2, 11);

            expect($number2)->toBe($number1 + 1);
        });
    });

    describe('concurrency', function () {
        it('does not produce duplicate numbers across multiple rapid calls', function () {
            $numbers = collect();

            for ($i = 0; $i < 20; $i++) {
                $numbers->push($this->service->generateCifNumber('001'));
            }

            expect($numbers->unique()->count())->toBe(20);
        });

        it('creates a sequence record in the database', function () {
            $this->service->generateCifNumber('001');

            $year = date('y');
            $sequence = Sequence::where('type', 'cif')
                ->where('prefix', "001{$year}")
                ->first();

            expect($sequence)->not->toBeNull()
                ->and($sequence->last_number)->toBe(1)
                ->and($sequence->padding)->toBe(7);
        });
    });
});
