<?php

namespace App\Exceptions\Deposit;

use App\Exceptions\DomainException;
use App\Models\DepositAccount;

class DepositPledgedException extends DomainException
{
    public static function cannotWithdraw(DepositAccount $account): static
    {
        return (new static('Deposito sedang dijaminkan, tidak dapat dicairkan'))
            ->withContext([
                'account_id' => $account->id,
                'pledge_reference' => $account->pledge_reference,
            ]);
    }
}
