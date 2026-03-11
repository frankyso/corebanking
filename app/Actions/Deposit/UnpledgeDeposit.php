<?php

namespace App\Actions\Deposit;

use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\DepositAccount;

class UnpledgeDeposit
{
    public function execute(DepositAccount $account): void
    {
        if (! $account->is_pledged) {
            throw InvalidDepositStatusException::notPledged($account);
        }

        $account->update([
            'is_pledged' => false,
            'pledge_reference' => null,
        ]);
    }
}
