<?php

use App\Actions\Accounting\CreateJournalEntry;
use App\Actions\Accounting\ReverseJournalEntry;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;

describe('ReverseJournalEntry', function (): void {
    beforeEach(function (): void {
        $this->createAction = app(CreateJournalEntry::class);
        $this->reverseAction = app(ReverseJournalEntry::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->cashAccount = ChartOfAccount::factory()->asset()->create([
            'account_code' => '1010',
            'account_name' => 'Kas',
            'normal_balance' => NormalBalance::Debit,
        ]);

        $this->liabilityAccount = ChartOfAccount::factory()->liability()->create([
            'account_code' => '2010',
            'account_name' => 'Tabungan',
            'normal_balance' => NormalBalance::Credit,
        ]);
    });

    it('creates a reversal journal with swapped debit/credit lines', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal to reverse',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
            creator: $this->user,
            autoPost: true,
        ));

        $reversed = $this->reverseAction->execute($journal, $this->approver, 'Koreksi');

        expect($reversed->status)->toBe(JournalStatus::Reversed)
            ->and($reversed->reversed_by)->toBe($this->approver->id)
            ->and($reversed->reversed_at)->not->toBeNull()
            ->and($reversed->reversal_reason)->toBe('Koreksi')
            ->and($reversed->reversal_journal_id)->not->toBeNull();

        $reversalJournal = JournalEntry::find($reversed->reversal_journal_id);
        expect($reversalJournal->status)->toBe(JournalStatus::Posted)
            ->and($reversalJournal->lines)->toHaveCount(2);
    });

    it('throws InvalidJournalStatusException when journal is not Posted', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Draft journal',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));

        $this->reverseAction->execute($journal, $this->approver, 'Koreksi');
    })->throws(InvalidJournalStatusException::class, 'Hanya jurnal yang sudah diposting yang dapat dibatalkan');
});
