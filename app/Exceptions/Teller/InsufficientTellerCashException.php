<?php

namespace App\Exceptions\Teller;

use App\Exceptions\DomainException;
use App\Models\TellerSession;

class InsufficientTellerCashException extends DomainException
{
    public static function insufficientBalance(TellerSession $session, float $amount): static
    {
        return (new static('Saldo kas teller tidak mencukupi'))
            ->withContext([
                'session_id' => $session->id,
                'current_balance' => (float) $session->current_balance,
                'requested_amount' => $amount,
            ]);
    }
}
