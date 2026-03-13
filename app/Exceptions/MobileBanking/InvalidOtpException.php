<?php

namespace App\Exceptions\MobileBanking;

use App\Exceptions\DomainException;

class InvalidOtpException extends DomainException
{
    public static function expired(): self
    {
        return new self('Kode OTP sudah kadaluarsa.');
    }

    public static function incorrectCode(int $remainingAttempts): self
    {
        return (new self('Kode OTP salah. Sisa percobaan: '.$remainingAttempts.'.'))
            ->withContext([
                'remaining_attempts' => $remainingAttempts,
            ]);
    }

    public static function alreadyUsed(): self
    {
        return new self('Kode OTP sudah digunakan.');
    }
}
