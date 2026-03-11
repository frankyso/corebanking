<?php

namespace App\Actions\Savings;

use App\Enums\SavingsAccountStatus;
use App\Models\SavingsAccount;

class MarkSavingsAccountDormant
{
    public function execute(SavingsAccount $account): void
    {
        if ($account->status !== SavingsAccountStatus::Active) {
            return;
        }

        $account->update([
            'status' => SavingsAccountStatus::Dormant,
            'dormant_at' => now(),
        ]);
    }
}
