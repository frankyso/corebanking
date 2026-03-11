<?php

namespace App\Services;

use App\Enums\AccountGroup;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
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
