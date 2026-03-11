<?php

namespace App\Actions\Loan;

use App\Enums\LoanStatus;
use App\Models\LoanAccount;

class UpdateLoanDpd
{
    public function execute(LoanAccount $account): void
    {
        $oldestOverdue = $account->schedules()
            ->where('is_paid', false)
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->first();

        if (! $oldestOverdue) {
            $account->update(['dpd' => 0, 'status' => LoanStatus::Current]);

            return;
        }

        $dpd = (int) $oldestOverdue->due_date->diffInDays(now());
        $account->update([
            'dpd' => $dpd,
            'status' => $dpd > 0 ? LoanStatus::Overdue : LoanStatus::Current,
        ]);
    }
}
