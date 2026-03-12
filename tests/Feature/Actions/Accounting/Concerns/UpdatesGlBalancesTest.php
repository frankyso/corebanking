<?php

use App\Actions\Accounting\CreateJournalEntry;
use App\Actions\Accounting\PostJournalEntry;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\JournalSource;
use App\Enums\NormalBalance;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\User;

describe('UpdatesGlBalances concern', function (): void {
    beforeEach(function (): void {
        $this->createAction = app(CreateJournalEntry::class);
        $this->postAction = app(PostJournalEntry::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->debitAccount = ChartOfAccount::factory()->asset()->create([
            'account_code' => '1010',
            'account_name' => 'Kas',
            'normal_balance' => NormalBalance::Debit,
        ]);

        $this->creditAccount = ChartOfAccount::factory()->liability()->create([
            'account_code' => '2010',
            'account_name' => 'Tabungan',
            'normal_balance' => NormalBalance::Credit,
        ]);
    });

    it('creates GlBalance record for new account and period', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Test GL balance creation',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 500000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->approver);

        $glBalance = GlBalance::where('chart_of_account_id', $this->debitAccount->id)
            ->where('period_year', now()->year)
            ->where('period_month', now()->month)
            ->first();

        expect($glBalance)->not->toBeNull()
            ->and((float) $glBalance->debit_total)->toBe(500000.00)
            ->and((float) $glBalance->credit_total)->toBe(0.00);
    });

    it('creates GlDailyBalance record for new date', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Test daily balance creation',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 300000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 300000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->approver);

        $dailyBalance = GlDailyBalance::where('chart_of_account_id', $this->debitAccount->id)
            ->where('balance_date', now()->format('Y-m-d'))
            ->first();

        expect($dailyBalance)->not->toBeNull()
            ->and((float) $dailyBalance->debit_total)->toBe(300000.00)
            ->and((float) $dailyBalance->credit_total)->toBe(0.00);
    });

    it('accumulates on existing GlBalance when posting multiple journals', function (): void {
        $journal1 = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'First journal',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 200000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 200000],
            ],
            creator: $this->user,
        ));
        $this->postAction->execute($journal1, $this->approver);

        $journal2 = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Second journal',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 300000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 300000],
            ],
            creator: $this->user,
        ));
        $this->postAction->execute($journal2, $this->approver);

        $glBalance = GlBalance::where('chart_of_account_id', $this->debitAccount->id)
            ->where('period_year', now()->year)
            ->where('period_month', now()->month)
            ->first();

        expect((float) $glBalance->debit_total)->toBe(500000.00)
            ->and((float) $glBalance->credit_total)->toBe(0.00);
    });

    it('calculates closing_balance correctly for debit-normal accounts', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Debit normal balance test',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 1000000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 1000000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->approver);

        $glBalance = GlBalance::where('chart_of_account_id', $this->debitAccount->id)->first();

        // Debit-normal: closing = opening + (debit - credit) = 0 + (1000000 - 0) = 1000000
        expect((float) $glBalance->closing_balance)->toBe(1000000.00)
            ->and((float) $glBalance->opening_balance)->toBe(0.00);
    });

    it('calculates closing_balance correctly for credit-normal accounts', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Credit normal balance test',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 750000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 750000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->approver);

        $glBalance = GlBalance::where('chart_of_account_id', $this->creditAccount->id)->first();

        // Credit-normal: closing = opening + (credit - debit) = 0 + (750000 - 0) = 750000
        expect((float) $glBalance->closing_balance)->toBe(750000.00)
            ->and((float) $glBalance->opening_balance)->toBe(0.00);
    });

    it('calculates daily closing_balance consistently with monthly balance', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Daily vs monthly consistency test',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->debitAccount->id, 'debit' => 400000, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 400000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->approver);

        $monthly = GlBalance::where('chart_of_account_id', $this->debitAccount->id)->first();
        $daily = GlDailyBalance::where('chart_of_account_id', $this->debitAccount->id)
            ->where('balance_date', now()->format('Y-m-d'))
            ->first();

        expect((float) $daily->closing_balance)->toBe((float) $monthly->closing_balance)
            ->and((float) $daily->debit_total)->toBe((float) $monthly->debit_total);
    });
});
