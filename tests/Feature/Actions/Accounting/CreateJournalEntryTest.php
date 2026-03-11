<?php

use App\Actions\Accounting\CreateJournalEntry;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\JournalEntry;
use App\Models\User;

describe('CreateJournalEntry', function (): void {
    beforeEach(function (): void {
        $this->action = app(CreateJournalEntry::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

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

    it('creates a draft journal with correct lines and totals', function (): void {
        $journal = $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal test',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
            creator: $this->user,
        ));

        expect($journal)->toBeInstanceOf(JournalEntry::class)
            ->and($journal->status)->toBe(JournalStatus::Draft)
            ->and($journal->approval_status)->toBe(ApprovalStatus::Pending)
            ->and((float) $journal->total_debit)->toBe(100000.00)
            ->and((float) $journal->total_credit)->toBe(100000.00)
            ->and($journal->created_by)->toBe($this->user->id)
            ->and($journal->journal_number)->not->toBeNull()
            ->and($journal->lines)->toHaveCount(2);
    });

    it('creates an auto-posted journal when autoPost is true', function (): void {
        $journal = $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal auto post',
            source: JournalSource::System,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
            ],
            creator: $this->user,
            autoPost: true,
        ));

        expect($journal->status)->toBe(JournalStatus::Posted)
            ->and($journal->approval_status)->toBe(ApprovalStatus::Approved)
            ->and($journal->posted_at)->not->toBeNull()
            ->and($journal->approved_by)->toBe($this->user->id);

        expect(GlBalance::count())->toBeGreaterThanOrEqual(2);
        expect(GlDailyBalance::count())->toBeGreaterThanOrEqual(2);
    });

    it('sets reference_type and reference_id when provided', function (): void {
        $journal = $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal ref',
            source: JournalSource::Teller,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
            referenceType: 'savings_transaction',
            referenceId: 99,
        ));

        expect($journal->reference_type)->toBe('savings_transaction')
            ->and($journal->reference_id)->toBe(99);
    });

    it('associates journal with branch when branchId is provided', function (): void {
        $journal = $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal cabang',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
            branchId: $this->branch->id,
        ));

        expect($journal->branch_id)->toBe($this->branch->id);
    });

    it('throws UnbalancedJournalException when fewer than 2 lines are provided', function (): void {
        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Jurnal harus memiliki minimal 2 baris');

    it('throws UnbalancedJournalException when a line has both debit and credit', function (): void {
        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 10000],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Baris jurnal tidak boleh memiliki debit dan kredit sekaligus');

    it('throws UnbalancedJournalException when a line has zero debit and zero credit', function (): void {
        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Baris jurnal harus memiliki debit atau kredit');

    it('throws UnbalancedJournalException when account is a header account', function (): void {
        $headerAccount = ChartOfAccount::factory()->header()->create();

        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $headerAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Akun tidak valid atau merupakan akun header');

    it('throws UnbalancedJournalException when account does not exist', function (): void {
        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => 99999, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Akun tidak valid atau merupakan akun header');

    it('throws UnbalancedJournalException when total debit does not equal total credit', function (): void {
        $this->action->execute(new CreateJournalData(
            journalDate: now(),
            description: 'Jurnal invalid',
            source: JournalSource::Manual,
            lines: [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 20000],
            ],
            creator: $this->user,
        ));
    })->throws(UnbalancedJournalException::class, 'Total debit');

    describe('GL balance updates', function (): void {
        it('creates GlBalance record for new account/period combination', function (): void {
            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'GL test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect($cashGl)->not->toBeNull()
                ->and((float) $cashGl->debit_total)->toBe(50000.00)
                ->and((float) $cashGl->credit_total)->toBe(0.00)
                ->and($cashGl->period_year)->toBe(now()->year)
                ->and($cashGl->period_month)->toBe(now()->month);
        });

        it('calculates closing_balance correctly for debit-normal accounts', function (): void {
            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'Debit normal test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect((float) $cashGl->closing_balance)->toBe(100000.00);
        });

        it('calculates closing_balance correctly for credit-normal accounts', function (): void {
            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'Credit normal test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $liabilityGl = GlBalance::where('chart_of_account_id', $this->liabilityAccount->id)->first();
            expect((float) $liabilityGl->closing_balance)->toBe(100000.00);
        });

        it('creates GlDailyBalance record with correct date', function (): void {
            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'Daily GL test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 25000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 25000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $dailyBalance = GlDailyBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect($dailyBalance)->not->toBeNull()
                ->and($dailyBalance->balance_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
        });

        it('updates existing GlBalance with cumulative totals', function (): void {
            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'First journal',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $this->action->execute(new CreateJournalData(
                journalDate: now(),
                description: 'Second journal',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 30000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 30000],
                ],
                creator: $this->user,
                autoPost: true,
            ));

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect((float) $cashGl->debit_total)->toBe(80000.00)
                ->and((float) $cashGl->closing_balance)->toBe(80000.00);
        });
    });
});
