<?php

namespace App\Services;

use App\Enums\AccountGroup;
use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string}>  $lines
     */
    public function createJournal(
        Carbon $journalDate,
        string $description,
        JournalSource $source,
        array $lines,
        User $creator,
        ?int $branchId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        bool $autoPost = false,
    ): JournalEntry {
        $this->validateLines($lines);

        return DB::transaction(function () use ($journalDate, $description, $source, $lines, $creator, $branchId, $referenceType, $referenceId, $autoPost) {
            $journalNumber = $this->sequenceService->generateJournalNumber();

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'journal_date' => $journalDate,
                'description' => $description,
                'source' => $source,
                'status' => $autoPost ? JournalStatus::Posted : JournalStatus::Draft,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'total_debit' => 0,
                'total_credit' => 0,
                'branch_id' => $branchId,
                'created_by' => $creator->id,
                'approval_status' => $autoPost ? ApprovalStatus::Approved : ApprovalStatus::Pending,
                'approved_by' => $autoPost ? $creator->id : null,
                'approved_at' => $autoPost ? now() : null,
                'posted_at' => $autoPost ? now() : null,
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create([
                    'chart_of_account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            $journal->recalculateTotals();

            if ($autoPost) {
                $this->updateGlBalances($journal);
            }

            return $journal->fresh(['lines.chartOfAccount']);
        });
    }

    public function postJournal(JournalEntry $journal, User $approver): JournalEntry
    {
        throw_if($journal->status !== JournalStatus::Draft, new \InvalidArgumentException('Jurnal harus berstatus Draft untuk diposting'));

        throw_unless($journal->isBalanced(), new \InvalidArgumentException('Total debit dan kredit tidak seimbang'));

        throw_unless($journal->canBeApprovedBy($approver), new \InvalidArgumentException('Anda tidak dapat menyetujui jurnal yang Anda buat sendiri'));

        return DB::transaction(function () use ($journal, $approver) {
            $journal->update([
                'status' => JournalStatus::Posted,
                'approval_status' => ApprovalStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            $this->updateGlBalances($journal);

            return $journal->fresh();
        });
    }

    public function reverseJournal(JournalEntry $journal, User $reverser, string $reason): JournalEntry
    {
        throw_if($journal->status !== JournalStatus::Posted, new \InvalidArgumentException('Hanya jurnal yang sudah diposting yang dapat dibatalkan'));

        return DB::transaction(function () use ($journal, $reverser, $reason) {
            $reversalLines = [];
            foreach ($journal->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->chart_of_account_id,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                    'description' => "Reversal: {$line->description}",
                ];
            }

            $reversalJournal = $this->createJournal(
                journalDate: now(),
                description: "Pembatalan jurnal {$journal->journal_number}: {$reason}",
                source: $journal->source,
                lines: $reversalLines,
                creator: $reverser,
                branchId: $journal->branch_id,
                referenceType: 'reversal',
                referenceId: $journal->id,
                autoPost: true,
            );

            $journal->update([
                'status' => JournalStatus::Reversed,
                'reversed_by' => $reverser->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_journal_id' => $reversalJournal->id,
            ]);

            return $journal->fresh();
        });
    }

    public function getTrialBalance(int $year, int $month, ?int $branchId = null): Collection
    {
        $query = GlBalance::query()
            ->with('chartOfAccount')
            ->forPeriod($year, $month)
            ->orderBy('chart_of_account_id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $balances = $query->get();

        if ($balances->isEmpty()) {
            return $this->calculateTrialBalanceFromJournals($year, $month, $branchId);
        }

        return $balances->map(function (GlBalance $balance): array {
            return [
                'account_code' => $balance->chartOfAccount->account_code,
                'account_name' => $balance->chartOfAccount->account_name,
                'account_group' => $balance->chartOfAccount->account_group,
                'debit' => (float) $balance->debit_total,
                'credit' => (float) $balance->credit_total,
                'closing_balance' => (float) $balance->closing_balance,
            ];
        });
    }

    public function getBalanceSheet(Carbon $date, ?int $branchId = null): array
    {
        $accounts = ChartOfAccount::query()
            ->where('is_header', false)
            ->where('is_active', true)
            ->whereIn('account_group', [AccountGroup::Asset, AccountGroup::Liability, AccountGroup::Equity])
            ->orderBy('account_code')
            ->get();

        $result = [
            'date' => $date->format('Y-m-d'),
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
        ];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account, $date, $branchId);

            $item = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'balance' => $balance,
            ];

            match ($account->account_group) {
                AccountGroup::Asset => $this->addToGroup($result, 'assets', 'total_assets', $item),
                AccountGroup::Liability => $this->addToGroup($result, 'liabilities', 'total_liabilities', $item),
                AccountGroup::Equity => $this->addToGroup($result, 'equity', 'total_equity', $item),
                default => null,
            };
        }

        return $result;
    }

    public function getIncomeStatement(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $accounts = ChartOfAccount::query()
            ->where('is_header', false)
            ->where('is_active', true)
            ->whereIn('account_group', [AccountGroup::Revenue, AccountGroup::Expense])
            ->orderBy('account_code')
            ->get();

        $result = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'revenues' => [],
            'expenses' => [],
            'total_revenue' => 0,
            'total_expense' => 0,
            'net_income' => 0,
        ];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalanceForPeriod($account, $startDate, $endDate, $branchId);

            $item = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'balance' => abs($balance),
            ];

            match ($account->account_group) {
                AccountGroup::Revenue => $this->addToGroup($result, 'revenues', 'total_revenue', $item),
                AccountGroup::Expense => $this->addToGroup($result, 'expenses', 'total_expense', $item),
                default => null,
            };
        }

        $result['net_income'] = bcsub((string) $result['total_revenue'], (string) $result['total_expense'], 2);

        return $result;
    }

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string}>  $lines
     */
    protected function validateLines(array $lines): void
    {
        throw_if(count($lines) < 2, new \InvalidArgumentException('Jurnal harus memiliki minimal 2 baris'));

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);

            throw_if((float) $debit > 0 && (float) $credit > 0, new \InvalidArgumentException('Baris jurnal tidak boleh memiliki debit dan kredit sekaligus'));

            throw_if((float) $debit == 0 && (float) $credit == 0, new \InvalidArgumentException('Baris jurnal harus memiliki debit atau kredit'));

            $account = ChartOfAccount::find($line['account_id']);
            throw_if(! $account || $account->is_header, new \InvalidArgumentException('Akun tidak valid atau merupakan akun header'));

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        throw_if(bccomp($totalDebit, $totalCredit, 2) !== 0, new \InvalidArgumentException("Total debit ({$totalDebit}) dan kredit ({$totalCredit}) tidak seimbang"));
    }

    protected function updateGlBalances(JournalEntry $journal): void
    {
        foreach ($journal->lines as $line) {
            $this->updateMonthlyBalance($line->chart_of_account_id, $journal->branch_id, $journal->journal_date, (float) $line->debit, (float) $line->credit);
            $this->updateDailyBalance($line->chart_of_account_id, $journal->branch_id, $journal->journal_date, (float) $line->debit, (float) $line->credit);
        }
    }

    protected function updateMonthlyBalance(int $accountId, ?int $branchId, Carbon $date, float $debit, float $credit): void
    {
        $account = ChartOfAccount::find($accountId);
        $year = $date->year;
        $month = $date->month;

        $balance = GlBalance::firstOrCreate(
            [
                'chart_of_account_id' => $accountId,
                'branch_id' => $branchId,
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'opening_balance' => 0,
                'debit_total' => 0,
                'credit_total' => 0,
                'closing_balance' => 0,
            ]
        );

        $newDebit = bcadd((string) $balance->debit_total, (string) $debit, 2);
        $newCredit = bcadd((string) $balance->credit_total, (string) $credit, 2);

        $netChange = $account->normal_balance === NormalBalance::Debit
            ? bcsub($newDebit, $newCredit, 2)
            : bcsub($newCredit, $newDebit, 2);

        $closingBalance = bcadd((string) $balance->opening_balance, $netChange, 2);

        $balance->update([
            'debit_total' => $newDebit,
            'credit_total' => $newCredit,
            'closing_balance' => $closingBalance,
        ]);
    }

    protected function updateDailyBalance(int $accountId, ?int $branchId, Carbon $date, float $debit, float $credit): void
    {
        $account = ChartOfAccount::find($accountId);

        $dailyBalance = GlDailyBalance::firstOrCreate(
            [
                'chart_of_account_id' => $accountId,
                'branch_id' => $branchId,
                'balance_date' => $date->format('Y-m-d'),
            ],
            [
                'opening_balance' => 0,
                'debit_total' => 0,
                'credit_total' => 0,
                'closing_balance' => 0,
            ]
        );

        $newDebit = bcadd((string) $dailyBalance->debit_total, (string) $debit, 2);
        $newCredit = bcadd((string) $dailyBalance->credit_total, (string) $credit, 2);

        $netChange = $account->normal_balance === NormalBalance::Debit
            ? bcsub($newDebit, $newCredit, 2)
            : bcsub($newCredit, $newDebit, 2);

        $closingBalance = bcadd((string) $dailyBalance->opening_balance, $netChange, 2);

        $dailyBalance->update([
            'debit_total' => $newDebit,
            'credit_total' => $newCredit,
            'closing_balance' => $closingBalance,
        ]);
    }

    protected function getAccountBalance(ChartOfAccount $account, Carbon $date, ?int $branchId = null): float
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.chart_of_account_id', $account->id)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->where('journal_entries.journal_date', '<=', $date->format('Y-m-d'));

        if ($branchId) {
            $query->where('journal_entries.branch_id', $branchId);
        }

        $totals = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')->first();

        if ($account->normal_balance === NormalBalance::Debit) {
            return (float) bcsub((string) $totals->total_debit, (string) $totals->total_credit, 2);
        }

        return (float) bcsub((string) $totals->total_credit, (string) $totals->total_debit, 2);
    }

    protected function getAccountBalanceForPeriod(ChartOfAccount $account, Carbon $startDate, Carbon $endDate, ?int $branchId = null): float
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.chart_of_account_id', $account->id)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereBetween('journal_entries.journal_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($branchId) {
            $query->where('journal_entries.branch_id', $branchId);
        }

        $totals = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')->first();

        if ($account->normal_balance === NormalBalance::Debit) {
            return (float) bcsub((string) $totals->total_debit, (string) $totals->total_credit, 2);
        }

        return (float) bcsub((string) $totals->total_credit, (string) $totals->total_debit, 2);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{account_code: string, account_name: string, balance: float}  $item
     */
    private function addToGroup(array &$result, string $groupKey, string $totalKey, array $item): void
    {
        $result[$groupKey][] = $item;
        $result[$totalKey] = bcadd((string) $result[$totalKey], (string) $item['balance'], 2);
    }

    protected function calculateTrialBalanceFromJournals(int $year, int $month, ?int $branchId = null): Collection
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereBetween('journal_entries.journal_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'chart_of_accounts.account_group')
            ->select([
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_group',
                DB::raw('SUM(journal_entry_lines.debit) as debit'),
                DB::raw('SUM(journal_entry_lines.credit) as credit'),
            ])
            ->orderBy('chart_of_accounts.account_code');

        if ($branchId) {
            $query->where('journal_entries.branch_id', $branchId);
        }

        return collect($query->get())->map(fn ($row): array => [
            'account_code' => $row->account_code,
            'account_name' => $row->account_name,
            'account_group' => $row->account_group,
            'debit' => (float) $row->debit,
            'credit' => (float) $row->credit,
            'closing_balance' => (float) bcsub((string) $row->debit, (string) $row->credit, 2),
        ]);
    }
}
