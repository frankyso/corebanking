<?php

use App\Enums\AccountGroup;
use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;

describe('AccountingService', function () {
    beforeEach(function () {
        $this->service = app(AccountingService::class);

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

        $this->revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'account_code' => '4010',
            'account_name' => 'Pendapatan Bunga',
            'normal_balance' => NormalBalance::Credit,
        ]);

        $this->expenseAccount = ChartOfAccount::factory()->expense()->create([
            'account_code' => '5010',
            'account_name' => 'Beban Bunga',
            'normal_balance' => NormalBalance::Debit,
        ]);
    });

    describe('createJournal', function () {
        it('creates a draft journal with correct lines and totals', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal test',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
            );

            expect($journal)->toBeInstanceOf(JournalEntry::class)
                ->and($journal->status)->toBe(JournalStatus::Draft)
                ->and($journal->approval_status)->toBe(ApprovalStatus::Pending)
                ->and((float) $journal->total_debit)->toBe(100000.00)
                ->and((float) $journal->total_credit)->toBe(100000.00)
                ->and($journal->created_by)->toBe($this->user->id)
                ->and($journal->journal_number)->not->toBeNull()
                ->and($journal->lines)->toHaveCount(2);
        });

        it('creates an auto-posted journal when autoPost is true', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal auto post',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            expect($journal->status)->toBe(JournalStatus::Posted)
                ->and($journal->approval_status)->toBe(ApprovalStatus::Approved)
                ->and($journal->posted_at)->not->toBeNull()
                ->and($journal->approved_by)->toBe($this->user->id);

            expect(GlBalance::count())->toBeGreaterThanOrEqual(2);
            expect(GlDailyBalance::count())->toBeGreaterThanOrEqual(2);
        });

        it('sets reference_type and reference_id when provided', function () {
            $journal = $this->service->createJournal(
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
            );

            expect($journal->reference_type)->toBe('savings_transaction')
                ->and($journal->reference_id)->toBe(99);
        });

        it('associates journal with branch when branchId is provided', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal cabang',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
                branchId: $this->branch->id,
            );

            expect($journal->branch_id)->toBe($this->branch->id);
        });

        it('throws when fewer than 2 lines are provided', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Jurnal harus memiliki minimal 2 baris');

        it('throws when a line has both debit and credit', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 10000],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Baris jurnal tidak boleh memiliki debit dan kredit sekaligus');

        it('throws when a line has zero debit and zero credit', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Baris jurnal harus memiliki debit atau kredit');

        it('throws when account is a header account', function () {
            $headerAccount = ChartOfAccount::factory()->header()->create();

            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $headerAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Akun tidak valid atau merupakan akun header');

        it('throws when account does not exist', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => 99999, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Akun tidak valid atau merupakan akun header');

        it('throws when total debit does not equal total credit', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal invalid',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 20000],
                ],
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Total debit');
    });

    describe('postJournal', function () {
        it('posts a draft journal and updates GL balances', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal to post',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
            );

            $posted = $this->service->postJournal($journal, $this->approver);

            expect($posted->status)->toBe(JournalStatus::Posted)
                ->and($posted->approval_status)->toBe(ApprovalStatus::Approved)
                ->and($posted->approved_by)->toBe($this->approver->id)
                ->and($posted->approved_at)->not->toBeNull()
                ->and($posted->posted_at)->not->toBeNull();

            $cashBalance = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect($cashBalance)->not->toBeNull()
                ->and((float) $cashBalance->debit_total)->toBe(100000.00);
        });

        it('throws when journal is not Draft', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Auto posted',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $this->service->postJournal($journal, $this->approver);
        })->throws(InvalidArgumentException::class, 'Jurnal harus berstatus Draft untuk diposting');

        it('throws when approver is the same as creator', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal self approve',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );

            $this->service->postJournal($journal, $this->user);
        })->throws(InvalidArgumentException::class, 'Anda tidak dapat menyetujui jurnal yang Anda buat sendiri');
    });

    describe('reverseJournal', function () {
        it('creates a reversal journal with swapped debit/credit lines', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Jurnal to reverse',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $reversed = $this->service->reverseJournal($journal, $this->approver, 'Koreksi');

            expect($reversed->status)->toBe(JournalStatus::Reversed)
                ->and($reversed->reversed_by)->toBe($this->approver->id)
                ->and($reversed->reversed_at)->not->toBeNull()
                ->and($reversed->reversal_reason)->toBe('Koreksi')
                ->and($reversed->reversal_journal_id)->not->toBeNull();

            $reversalJournal = JournalEntry::find($reversed->reversal_journal_id);
            expect($reversalJournal->status)->toBe(JournalStatus::Posted)
                ->and($reversalJournal->lines)->toHaveCount(2);
        });

        it('throws when journal is not Posted', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Draft journal',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 10000],
                ],
                creator: $this->user,
            );

            $this->service->reverseJournal($journal, $this->approver, 'Koreksi');
        })->throws(InvalidArgumentException::class, 'Hanya jurnal yang sudah diposting yang dapat dibatalkan');
    });

    describe('GL balance updates', function () {
        it('creates GlBalance record for new account/period combination', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'GL test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect($cashGl)->not->toBeNull()
                ->and((float) $cashGl->debit_total)->toBe(50000.00)
                ->and((float) $cashGl->credit_total)->toBe(0.00)
                ->and($cashGl->period_year)->toBe(now()->year)
                ->and($cashGl->period_month)->toBe(now()->month);
        });

        it('calculates closing_balance correctly for debit-normal accounts', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Debit normal test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            // Debit normal: closing = opening(0) + (debit - credit) = 0 + (100000 - 0) = 100000
            expect((float) $cashGl->closing_balance)->toBe(100000.00);
        });

        it('calculates closing_balance correctly for credit-normal accounts', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Credit normal test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $liabilityGl = GlBalance::where('chart_of_account_id', $this->liabilityAccount->id)->first();
            // Credit normal: closing = opening(0) + (credit - debit) = 0 + (100000 - 0) = 100000
            expect((float) $liabilityGl->closing_balance)->toBe(100000.00);
        });

        it('creates GlDailyBalance record with correct date', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Daily GL test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 25000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 25000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $dailyBalance = GlDailyBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect($dailyBalance)->not->toBeNull()
                ->and($dailyBalance->balance_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
        });

        it('updates existing GlBalance with cumulative totals', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'First journal',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $this->service->createJournal(
                journalDate: now(),
                description: 'Second journal',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 30000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 30000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $cashGl = GlBalance::where('chart_of_account_id', $this->cashAccount->id)->first();
            expect((float) $cashGl->debit_total)->toBe(80000.00)
                ->and((float) $cashGl->closing_balance)->toBe(80000.00);
        });
    });

    describe('getTrialBalance', function () {
        it('returns trial balance from GlBalance records', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Trial balance test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $trialBalance = $this->service->getTrialBalance(now()->year, now()->month);

            expect($trialBalance)->toHaveCount(2);
            $cashEntry = $trialBalance->firstWhere('account_code', '1010');
            expect($cashEntry)->not->toBeNull()
                ->and((float) $cashEntry['debit'])->toBe(100000.00);
        });

        it('calculates trial balance from journals when no GlBalance records exist', function () {
            $journal = $this->service->createJournal(
                journalDate: now(),
                description: 'Fallback test',
                source: JournalSource::Manual,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
            );
            $this->service->postJournal($journal, $this->approver);

            // Delete GL balances to force fallback
            GlBalance::truncate();

            $trialBalance = $this->service->getTrialBalance(now()->year, now()->month);
            expect($trialBalance)->toHaveCount(2);
        });

        it('filters by branch', function () {
            $branch2 = Branch::create(['code' => '002', 'name' => 'Cabang 2', 'is_active' => true]);

            $this->service->createJournal(
                journalDate: now(),
                description: 'Branch 1',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                branchId: $this->branch->id,
                autoPost: true,
            );

            $trialBalance = $this->service->getTrialBalance(now()->year, now()->month, $branch2->id);
            expect($trialBalance)->toHaveCount(0);
        });
    });

    describe('getBalanceSheet', function () {
        it('returns correct structure with assets, liabilities, and equity', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Balance sheet test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->liabilityAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $bs = $this->service->getBalanceSheet(now());

            expect($bs)->toHaveKeys(['date', 'assets', 'liabilities', 'equity', 'total_assets', 'total_liabilities', 'total_equity'])
                ->and($bs['assets'])->toBeArray()
                ->and((float) $bs['total_assets'])->toBe(100000.00)
                ->and((float) $bs['total_liabilities'])->toBe(100000.00);
        });

        it('excludes header and inactive accounts', function () {
            ChartOfAccount::factory()->header()->create(['account_group' => AccountGroup::Asset]);
            ChartOfAccount::factory()->inactive()->create(['account_group' => AccountGroup::Asset]);

            $bs = $this->service->getBalanceSheet(now());

            $accountCodes = collect($bs['assets'])->pluck('account_code');
            expect($accountCodes)->not->toContain(fn ($code) => str_starts_with($code, 'HDR'));
        });
    });

    describe('getIncomeStatement', function () {
        it('returns correct structure with revenues and expenses', function () {
            $this->service->createJournal(
                journalDate: now(),
                description: 'Income test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $this->service->createJournal(
                journalDate: now(),
                description: 'Expense test',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->expenseAccount->id, 'debit' => 40000, 'credit' => 0],
                    ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 40000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $is = $this->service->getIncomeStatement(now()->startOfMonth(), now()->endOfMonth());

            expect($is)->toHaveKeys(['start_date', 'end_date', 'revenues', 'expenses', 'total_revenue', 'total_expense', 'net_income'])
                ->and((float) $is['total_revenue'])->toBe(100000.00)
                ->and((float) $is['total_expense'])->toBe(40000.00)
                ->and((float) $is['net_income'])->toBe(60000.00);
        });

        it('filters by date range', function () {
            $this->service->createJournal(
                journalDate: now()->subMonth(),
                description: 'Last month',
                source: JournalSource::System,
                lines: [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
                creator: $this->user,
                autoPost: true,
            );

            $is = $this->service->getIncomeStatement(now()->startOfMonth(), now()->endOfMonth());
            expect((float) $is['total_revenue'])->toBe(0.00);
        });
    });
});
