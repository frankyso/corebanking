<?php

namespace App\Exceptions\Savings;

use App\Exceptions\DomainException;
use App\Models\SavingsAccount;

class InvalidSavingsAccountStatusException extends DomainException
{
    public static function notActive(SavingsAccount $account): static
    {
        return (new static('Rekening tidak aktif'))
            ->withContext([
                'account_id' => $account->id,
                'status' => $account->status->value,
            ]);
    }

    public static function notFrozen(SavingsAccount $account): static
    {
        return (new static('Rekening tidak dalam status dibekukan'))
            ->withContext([
                'account_id' => $account->id,
                'status' => $account->status->value,
            ]);
    }

    public static function cannotClose(SavingsAccount $account): static
    {
        return (new static('Rekening tidak dapat ditutup'))
            ->withContext([
                'account_id' => $account->id,
                'status' => $account->status->value,
            ]);
    }

    public static function hasHoldAmount(SavingsAccount $account): static
    {
        return (new static('Rekening masih memiliki saldo diblokir'))
            ->withContext([
                'account_id' => $account->id,
                'hold_amount' => (float) $account->hold_amount,
            ]);
    }
}
