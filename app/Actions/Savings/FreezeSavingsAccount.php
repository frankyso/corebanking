<?php

namespace App\Actions\Savings;

use App\Enums\SavingsAccountStatus;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\SavingsAccount;

class FreezeSavingsAccount
{
    public function execute(SavingsAccount $account): void
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw InvalidSavingsAccountStatusException::notActive($account);
        }

        $account->update(['status' => SavingsAccountStatus::Frozen]);
    }
}
