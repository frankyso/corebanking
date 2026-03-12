<?php

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingReportService;
use Carbon\Carbon;

describe('AccountingReportService', function (): void {
    beforeEach(function (): void {
        $this->service = app(AccountingReportService::class);
        $this->branch = Branch::factory()->create();
    });

    describe('getTrialBalance', function (): void {
        it('returns data from GlBalance records', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();
            $liability = ChartOfAccount::factory()->liability()->create();

            GlBalance::create([
                'chart_of_account_id' => $asset->id,
                'branch_id' => $this->branch->id,
                'period_year' => 2026,
                'period_month' => 1,
                'opening_balance' => 0,
                'debit_total' => 5000000,
                'credit_total' => 1000000,
                'closing_balance' => 4000000,
            ]);

            GlBalance::create([
                'chart_of_account_id' => $liability->id,
                'branch_id' => $this->branch->id,
                'period_year' => 2026,
                'period_month' => 1,
                'opening_balance' => 0,
                'debit_total' => 500000,
                'credit_total' => 3000000,
                'closing_balance' => 2500000,
            ]);

            $result = $this->service->getTrialBalance(2026, 1);

            expect($result)->toHaveCount(2)
                ->and($result[0]['account_code'])->toBe($asset->account_code)
                ->and($result[0]['debit'])->toBe(5000000.0)
                ->and($result[0]['credit'])->toBe(1000000.0)
                ->and($result[0]['closing_balance'])->toBe(4000000.0)
                ->and($result[1]['account_code'])->toBe($liability->account_code)
                ->and($result[1]['debit'])->toBe(500000.0)
                ->and($result[1]['credit'])->toBe(3000000.0);
        });

        it('falls back to journals when no GlBalance exists', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();

            $journal = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 3, 15),
                'total_debit' => 1000000,
                'total_credit' => 1000000,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 1000000,
                'credit' => 0,
                'description' => 'Test debit',
            ]);

            $result = $this->service->getTrialBalance(2026, 3);

            expect($result)->toHaveCount(1)
                ->and($result->first()['account_code'])->toBe($asset->account_code)
                ->and($result->first()['debit'])->toBe(1000000.0)
                ->and($result->first()['credit'])->toBe(0.0)
                ->and($result->first()['closing_balance'])->toBe(1000000.0);
        });

        it('filters by branchId', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();
            $otherBranch = Branch::factory()->create();

            GlBalance::create([
                'chart_of_account_id' => $asset->id,
                'branch_id' => $this->branch->id,
                'period_year' => 2026,
                'period_month' => 1,
                'debit_total' => 5000000,
                'credit_total' => 0,
                'closing_balance' => 5000000,
            ]);

            GlBalance::create([
                'chart_of_account_id' => $asset->id,
                'branch_id' => $otherBranch->id,
                'period_year' => 2026,
                'period_month' => 1,
                'debit_total' => 2000000,
                'credit_total' => 0,
                'closing_balance' => 2000000,
            ]);

            $result = $this->service->getTrialBalance(2026, 1, $this->branch->id);

            expect($result)->toHaveCount(1)
                ->and($result[0]['debit'])->toBe(5000000.0);
        });

        it('returns empty collection when no data exists', function (): void {
            $result = $this->service->getTrialBalance(2026, 12);

            expect($result)->toBeEmpty();
        });
    });

    describe('getBalanceSheet', function (): void {
        it('returns correct structure', function (): void {
            $date = Carbon::create(2026, 3, 31);
            $result = $this->service->getBalanceSheet($date);

            expect($result)->toHaveKeys([
                'date', 'assets', 'liabilities', 'equity',
                'total_assets', 'total_liabilities', 'total_equity',
            ])
                ->and($result['date'])->toBe('2026-03-31')
                ->and($result['assets'])->toBeArray()
                ->and($result['liabilities'])->toBeArray()
                ->and($result['equity'])->toBeArray();
        });

        it('calculates asset balance using debit normal', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();
            $date = Carbon::create(2026, 3, 31);

            $journal = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 3, 15),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 10000000,
                'credit' => 3000000,
                'description' => 'Test',
            ]);

            $result = $this->service->getBalanceSheet($date);

            expect($result['assets'])->toHaveCount(1)
                ->and($result['assets'][0]['balance'])->toBe(7000000.0)
                ->and($result['total_assets'])->toBe('7000000.00');
        });

        it('calculates liability balance using credit normal', function (): void {
            $liability = ChartOfAccount::factory()->liability()->create();
            $date = Carbon::create(2026, 3, 31);

            $journal = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 3, 10),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $liability->id,
                'debit' => 1000000,
                'credit' => 8000000,
                'description' => 'Test',
            ]);

            $result = $this->service->getBalanceSheet($date);

            expect($result['liabilities'])->toHaveCount(1)
                ->and($result['liabilities'][0]['balance'])->toBe(7000000.0)
                ->and($result['total_liabilities'])->toBe('7000000.00');
        });

        it('filters by branchId', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();
            $otherBranch = Branch::factory()->create();
            $date = Carbon::create(2026, 3, 31);

            $journal1 = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 3, 10),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal1->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 5000000,
                'credit' => 0,
                'description' => 'Branch A',
            ]);

            $journal2 = JournalEntry::factory()->posted()->create([
                'branch_id' => $otherBranch->id,
                'journal_date' => Carbon::create(2026, 3, 10),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal2->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 3000000,
                'credit' => 0,
                'description' => 'Branch B',
            ]);

            $result = $this->service->getBalanceSheet($date, $this->branch->id);

            expect($result['total_assets'])->toBe('5000000.00');
        });
    });

    describe('getIncomeStatement', function (): void {
        it('returns correct structure', function (): void {
            $startDate = Carbon::create(2026, 1, 1);
            $endDate = Carbon::create(2026, 3, 31);

            $result = $this->service->getIncomeStatement($startDate, $endDate);

            expect($result)->toHaveKeys([
                'start_date', 'end_date', 'revenues', 'expenses',
                'total_revenue', 'total_expense', 'net_income',
            ])
                ->and($result['start_date'])->toBe('2026-01-01')
                ->and($result['end_date'])->toBe('2026-03-31');
        });

        it('calculates net_income as revenue minus expense', function (): void {
            $revenue = ChartOfAccount::factory()->revenue()->create();
            $expense = ChartOfAccount::factory()->expense()->create();

            $startDate = Carbon::create(2026, 1, 1);
            $endDate = Carbon::create(2026, 3, 31);

            $journal = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 2, 15),
            ]);

            // Revenue: credit normal, so credit > debit = positive balance
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $revenue->id,
                'debit' => 0,
                'credit' => 10000000,
                'description' => 'Revenue entry',
            ]);

            // Expense: debit normal, so debit > credit = positive balance
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'chart_of_account_id' => $expense->id,
                'debit' => 4000000,
                'credit' => 0,
                'description' => 'Expense entry',
            ]);

            $result = $this->service->getIncomeStatement($startDate, $endDate);

            expect($result['total_revenue'])->toBe('10000000.00')
                ->and($result['total_expense'])->toBe('4000000.00')
                ->and($result['net_income'])->toBe('6000000.00');
        });

        it('filters by branchId', function (): void {
            $revenue = ChartOfAccount::factory()->revenue()->create();
            $otherBranch = Branch::factory()->create();

            $startDate = Carbon::create(2026, 1, 1);
            $endDate = Carbon::create(2026, 3, 31);

            $journal1 = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 2, 15),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal1->id,
                'chart_of_account_id' => $revenue->id,
                'debit' => 0,
                'credit' => 7000000,
                'description' => 'Branch A revenue',
            ]);

            $journal2 = JournalEntry::factory()->posted()->create([
                'branch_id' => $otherBranch->id,
                'journal_date' => Carbon::create(2026, 2, 15),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal2->id,
                'chart_of_account_id' => $revenue->id,
                'debit' => 0,
                'credit' => 3000000,
                'description' => 'Branch B revenue',
            ]);

            $result = $this->service->getIncomeStatement($startDate, $endDate, $this->branch->id);

            expect($result['total_revenue'])->toBe('7000000.00');
        });
    });

    describe('calculateTrialBalanceFromJournals', function (): void {
        it('aggregates journal lines by account', function (): void {
            $asset = ChartOfAccount::factory()->asset()->create();
            $liability = ChartOfAccount::factory()->liability()->create();

            $journal1 = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 2, 10),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal1->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 5000000,
                'credit' => 0,
                'description' => 'Debit asset',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal1->id,
                'chart_of_account_id' => $liability->id,
                'debit' => 0,
                'credit' => 5000000,
                'description' => 'Credit liability',
            ]);

            $journal2 = JournalEntry::factory()->posted()->create([
                'branch_id' => $this->branch->id,
                'journal_date' => Carbon::create(2026, 2, 20),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal2->id,
                'chart_of_account_id' => $asset->id,
                'debit' => 3000000,
                'credit' => 0,
                'description' => 'Debit asset 2',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal2->id,
                'chart_of_account_id' => $liability->id,
                'debit' => 0,
                'credit' => 3000000,
                'description' => 'Credit liability 2',
            ]);

            // No GlBalance records, so getTrialBalance will fall back to calculateTrialBalanceFromJournals
            $result = $this->service->getTrialBalance(2026, 2);

            expect($result)->toHaveCount(2);

            $assetRow = $result->firstWhere('account_code', $asset->account_code);
            expect($assetRow['debit'])->toBe(8000000.0)
                ->and($assetRow['credit'])->toBe(0.0)
                ->and($assetRow['closing_balance'])->toBe(8000000.0);

            $liabilityRow = $result->firstWhere('account_code', $liability->account_code);
            expect($liabilityRow['debit'])->toBe(0.0)
                ->and($liabilityRow['credit'])->toBe(8000000.0)
                ->and($liabilityRow['closing_balance'])->toBe(-8000000.0);
        });
    });
});
