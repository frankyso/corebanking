<?php

namespace App\Exceptions\MobileBanking;

use App\Exceptions\DomainException;

class TransferException extends DomainException
{
    public static function sameAccount(): self
    {
        return new self('Rekening sumber dan tujuan tidak boleh sama.');
    }

    public static function destinationNotFound(string $accountNumber): self
    {
        return (new self('Rekening tujuan tidak ditemukan.'))
            ->withContext([
                'account_number' => $accountNumber,
            ]);
    }

    public static function dailyLimitExceeded(float $limit): self
    {
        return (new self('Batas transfer harian terlampaui.'))
            ->withContext([
                'daily_limit' => $limit,
            ]);
    }
}
