<?php

namespace App\Exceptions\Deposit;

use App\Exceptions\DomainException;
use App\Models\DepositAccount;

class InvalidDepositStatusException extends DomainException
{
    public static function notActive(DepositAccount $account): static
    {
        return (new static('Deposito tidak dalam status aktif'))
            ->withContext([
                'account_id' => $account->id,
                'current_status' => $account->status?->value,
            ]);
    }

    public static function notMatured(DepositAccount $account): static
    {
        return (new static('Deposito belum jatuh tempo'))
            ->withContext([
                'account_id' => $account->id,
                'maturity_date' => $account->maturity_date?->toDateString(),
            ]);
    }

    public static function alreadyPledged(DepositAccount $account): static
    {
        return (new static('Deposito sudah dijaminkan'))
            ->withContext([
                'account_id' => $account->id,
                'pledge_reference' => $account->pledge_reference,
            ]);
    }

    public static function notPledged(DepositAccount $account): static
    {
        return (new static('Deposito tidak sedang dijaminkan'))
            ->withContext([
                'account_id' => $account->id,
            ]);
    }
}
