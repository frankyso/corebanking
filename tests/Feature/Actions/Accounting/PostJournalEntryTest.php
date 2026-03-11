<?php

use App\Actions\Accounting\CreateJournalEntry;
use App\Actions\Accounting\PostJournalEntry;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\User;

describe('PostJournalEntry', function (): void {
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

    it('posts a draft journal and updates GL balances', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal to post',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
            creator: $this->user,
        ));

        $posted = $this->postAction->execute($journal, $this->approver);

        expect($posted->status)->toBe(JournalStatus::Posted)
            ->and($posted->approval_status)->toBe(ApprovalStatus::Approved)
            ->and($posted->approved_by)->toBe($this->approver->id)
            ->and($posted->approved_at)->not->toBeNull()
            ->and($posted->posted_at)->not->toBeNull();

        $cashBalance = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
        expect($cashBalance)->not->toBeNull()
            ->and((float) $cashBalance->debit_total)->toBe(100000.00);
    });

    it('throws InvalidJournalStatusException when journal is not Draft', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Auto posted',
            source: JournalSource::System,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
            autoPost: true,
        ));

        $this->postAction->execute($journal, $this->approver);
    })->throws(InvalidJournalStatusException::class, 'Jurnal harus berstatus Draft untuk diposting');

    it('throws InvalidJournalStatusException when approver is the same as creator', function (): void {
        $journal = $this->createAction->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal self approve',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));

        $this->postAction->execute($journal, $this->user);
    })->throws(InvalidJournalStatusException::class, 'Anda tidak dapat menyetujui jurnal yang Anda buat sendiri');
});
