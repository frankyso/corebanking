<?php

use App\Enums\AccountGroup;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
});

// ============================================================================
// JournalEntry - Additional Coverage
// ============================================================================
describe('JournalEntry additional coverage', function (): void {
    it('isDraft returns false for posted status', function (): void {
        $journal = JournalEntry::factory()->posted()->create();

        expect($journal->isDraft())->toBeFalse()
            ->and($journal->isPosted())->toBeTrue();
    });

    it('isReversed returns false for draft status', function (): void {
        $journal = JournalEntry::factory()->create(['status' => JournalStatus::Draft]);

        expect($journal->isReversed())->toBeFalse();
    });

    it('recalculateTotals handles no lines', function (): void {
        $journal = JournalEntry::factory()->create([
            'total_debit' => 1000000,
            'total_credit' => 1000000,
        ]);

        $journal->recalculateTotals();
        $journal->refresh();

        expect($journal->total_debit)->toBe('0.00')
            ->and($journal->total_credit)->toBe('0.00');
    });

    it('recalculateTotals handles multiple lines', function (): void {
        $journal = JournalEntry::factory()->create([
            'total_debit' => 0,
            'total_credit' => 0,
        ]);
        $coa1 = ChartOfAccount::factory()->create();
        $coa2 = ChartOfAccount::factory()->create();

        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa1->id,
            'description' => 'Debit 1',
            'debit' => 300000,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa2->id,
            'description' => 'Debit 2',
            'debit' => 200000,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa1->id,
            'description' => 'Credit 1',
            'debit' => 0,
            'credit' => 500000,
        ]);

        $journal->recalculateTotals();
        $journal->refresh();

        expect($journal->total_debit)->toBe('500000.00')
            ->and($journal->total_credit)->toBe('500000.00')
            ->and($journal->isBalanced())->toBeTrue();
    });

    it('scope byDateRange excludes outside dates', function (): void {
        JournalEntry::factory()->create(['journal_date' => '2026-01-15']);
        JournalEntry::factory()->create(['journal_date' => '2026-02-15']);
        JournalEntry::factory()->create(['journal_date' => '2026-03-15']);

        $range = JournalEntry::byDateRange('2026-02-01', '2026-02-28')->get();

        expect($range)->toHaveCount(1)
            ->and($range->first()->journal_date->toDateString())->toBe('2026-02-15');
    });

    it('scope bySource filters System source', function (): void {
        JournalEntry::factory()->create(['source' => JournalSource::System]);
        JournalEntry::factory()->create(['source' => JournalSource::Manual]);

        $system = JournalEntry::bySource(JournalSource::System)->get();

        expect($system)->toHaveCount(1)
            ->and($system->first()->source)->toBe(JournalSource::System);
    });

    it('reversalJournal relationship links to reversal', function (): void {
        $original = JournalEntry::factory()->create(['status' => JournalStatus::Reversed]);
        $reversal = JournalEntry::factory()->create();
        $original->update(['reversal_journal_id' => $reversal->id]);
        $original->refresh();

        expect($original->reversalJournal)->toBeInstanceOf(JournalEntry::class)
            ->and($original->reversalJournal->id)->toBe($reversal->id);
    });

    it('reversedBy relationship links to reversing user', function (): void {
        $journal = JournalEntry::factory()->create([
            'status' => JournalStatus::Reversed,
            'reversed_by' => $this->user->id,
            'reversed_at' => now(),
        ]);

        expect($journal->reversedBy)->toBeInstanceOf(User::class)
            ->and($journal->reversedBy->id)->toBe($this->user->id)
            ->and($journal->reversed_at)->toBeInstanceOf(Carbon::class);
    });

    it('casts posted_at as datetime', function (): void {
        $journal = JournalEntry::factory()->posted()->create();

        expect($journal->posted_at)->toBeInstanceOf(Carbon::class);
    });
});

// ============================================================================
// JournalEntryLine - Additional Coverage
// ============================================================================
describe('JournalEntryLine additional coverage', function (): void {
    it('links to chartOfAccount correctly', function (): void {
        $journal = JournalEntry::factory()->create();
        $coa = ChartOfAccount::factory()->asset()->create();

        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Debit kas',
            'debit' => 5000000,
            'credit' => 0,
        ]);

        expect($line->chartOfAccount->account_group)->toBe(AccountGroup::Asset)
            ->and($line->chartOfAccount->normal_balance)->toBe(NormalBalance::Debit);
    });
});

// ============================================================================
// GlBalance - Additional Coverage
// ============================================================================
describe('GlBalance additional coverage', function (): void {
    it('casts all decimal fields correctly', function (): void {
        $coa = ChartOfAccount::factory()->create();

        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 50000000.50,
            'debit_total' => 10000000.25,
            'credit_total' => 5000000.75,
            'closing_balance' => 55000000.00,
        ]);

        expect($balance->opening_balance)->toBe('50000000.50')
            ->and($balance->debit_total)->toBe('10000000.25')
            ->and($balance->credit_total)->toBe('5000000.75')
            ->and($balance->closing_balance)->toBe('55000000.00');
    });

    it('scope forPeriod filters by both year and month', function (): void {
        $coa = ChartOfAccount::factory()->create();

        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2025,
            'period_month' => 12,
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);
        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 1,
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        expect(GlBalance::forPeriod(2025, 12)->count())->toBe(1)
            ->and(GlBalance::forPeriod(2026, 1)->count())->toBe(1)
            ->and(GlBalance::forPeriod(2026, 12)->count())->toBe(0);
    });

    it('branch relationship returns branch', function (): void {
        $coa = ChartOfAccount::factory()->create();

        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        expect($balance->branch->id)->toBe($this->branch->id)
            ->and($balance->chartOfAccount->id)->toBe($coa->id);
    });
});

// ============================================================================
// GlDailyBalance - Additional Coverage
// ============================================================================
describe('GlDailyBalance additional coverage', function (): void {
    it('casts balance_date as date and decimals correctly', function (): void {
        $coa = ChartOfAccount::factory()->create();

        $balance = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 100000000.00,
            'debit_total' => 25000000.50,
            'credit_total' => 15000000.25,
            'closing_balance' => 110000000.25,
        ]);

        expect($balance->balance_date)->toBeInstanceOf(Carbon::class)
            ->and($balance->balance_date->toDateString())->toBe('2026-03-10')
            ->and($balance->opening_balance)->toBe('100000000.00')
            ->and($balance->debit_total)->toBe('25000000.50')
            ->and($balance->credit_total)->toBe('15000000.25')
            ->and($balance->closing_balance)->toBe('110000000.25');
    });

    it('scope forDate returns only matching date', function (): void {
        $coa = ChartOfAccount::factory()->create();

        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-09',
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);
        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        expect(GlDailyBalance::forDate('2026-03-09')->count())->toBe(1)
            ->and(GlDailyBalance::forDate('2026-03-11')->count())->toBe(0);
    });
});

// ============================================================================
// ChartOfAccount - Additional Coverage
// ============================================================================
describe('ChartOfAccount additional coverage', function (): void {
    it('scope postable excludes inactive accounts', function (): void {
        ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);
        ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => false]);

        $postable = ChartOfAccount::postable()->get();

        expect($postable->every(fn ($c): bool => $c->is_active && ! $c->is_header))->toBeTrue();
    });

    it('fullName accessor combines code and name', function (): void {
        $coa = ChartOfAccount::factory()->create([
            'account_code' => '21001',
            'account_name' => 'Tabungan',
        ]);

        expect($coa->full_name)->toBe('21001 - Tabungan');
    });

    it('children relationship returns child accounts', function (): void {
        $parent = ChartOfAccount::factory()->header()->create();
        ChartOfAccount::factory()->childOf($parent)->create();
        ChartOfAccount::factory()->childOf($parent)->create();

        expect($parent->children)->toHaveCount(2);
    });
});
