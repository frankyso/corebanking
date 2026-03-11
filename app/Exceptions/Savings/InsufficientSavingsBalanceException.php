<?php

namespace App\Exceptions\Savings;

use App\Exceptions\DomainException;
use App\Models\SavingsAccount;

class InsufficientSavingsBalanceException extends DomainException
{
    public static function forWithdrawal(SavingsAccount $account, float $amount): static
    {
        return (new static('Saldo tidak mencukupi'))
            ->withContext([
                'account_id' => $account->id,
                'requested_amount' => $amount,
                'available_balance' => (float) $account->available_balance,
            ]);
    }

    public static function forHold(SavingsAccount $account, float $amount): static
    {
        return (new static('Saldo tersedia tidak mencukupi untuk pemblokiran'))
            ->withContext([
                'account_id' => $account->id,
                'requested_amount' => $amount,
                'available_balance' => (float) $account->available_balance,
            ]);
    }

    public static function unholdExceedsHeldAmount(SavingsAccount $account, float $amount): static
    {
        return (new static('Jumlah melebihi saldo yang diblokir'))
            ->withContext([
                'account_id' => $account->id,
                'requested_amount' => $amount,
                'hold_amount' => (float) $account->hold_amount,
            ]);
    }

    public static function invalidAmount(string $operation): static
    {
        return (new static("Jumlah {$operation} harus lebih dari 0"))
            ->withContext([
                'operation' => $operation,
            ]);
    }
}
