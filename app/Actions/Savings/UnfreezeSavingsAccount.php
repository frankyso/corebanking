<?php

namespace App\Actions\Savings;

use App\Enums\SavingsAccountStatus;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\SavingsAccount;

class UnfreezeSavingsAccount
{
    public function execute(SavingsAccount $account): void
    {
        if ($account->status !== SavingsAccountStatus::Frozen) {
            throw InvalidSavingsAccountStatusException::notFrozen($account);
        }

        $account->update(['status' => SavingsAccountStatus::Active]);
    }
}
