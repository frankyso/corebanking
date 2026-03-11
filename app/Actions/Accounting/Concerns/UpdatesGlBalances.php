<?php

namespace App\Actions\Accounting\Concerns;

use App\Enums\NormalBalance;
use App\Models\ChartOfAccount;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\JournalEntry;
use Carbon\Carbon;

trait UpdatesGlBalances
{
    protected function updateGlBalances(JournalEntry $journal): void
    {
        foreach ($journal->lines as $line) {
            $this->updateMonthlyBalance(
                $line->chart_of_account_id,
                $journal->branch_id,
                $journal->journal_date,
                (float) $line->debit,
                (float) $line->credit,
            );
            $this->updateDailyBalance(
                $line->chart_of_account_id,
                $journal->branch_id,
                $journal->journal_date,
                (float) $line->debit,
                (float) $line->credit,
            );
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
}
