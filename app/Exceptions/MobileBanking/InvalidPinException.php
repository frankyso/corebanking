<?php

namespace App\Exceptions\MobileBanking;

use App\Exceptions\DomainException;
use Carbon\Carbon;

class InvalidPinException extends DomainException
{
    public static function incorrectPin(int $remainingAttempts): self
    {
        return (new self('PIN salah. Sisa percobaan: '.$remainingAttempts.'.'))
            ->withContext([
                'remaining_attempts' => $remainingAttempts,
            ]);
    }

    public static function pinLocked(Carbon $until): self
    {
        return (new self('PIN terkunci hingga '.$until->format('H:i').'.'))
            ->withContext([
                'locked_until' => $until->toIso8601String(),
            ]);
    }
}
