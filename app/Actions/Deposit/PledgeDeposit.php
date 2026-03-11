<?php

namespace App\Actions\Deposit;

use App\Enums\DepositStatus;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\DepositAccount;

class PledgeDeposit
{
    public function execute(DepositAccount $account, string $pledgeReference): void
    {
        if ($account->status !== DepositStatus::Active) {
            throw InvalidDepositStatusException::notActive($account);
        }

        if ($account->is_pledged) {
            throw InvalidDepositStatusException::alreadyPledged($account);
        }

        $account->update([
            'is_pledged' => true,
            'pledge_reference' => $pledgeReference,
        ]);
    }
}
