<?php

namespace App\Exceptions\Eod;

use App\Exceptions\DomainException;

class EodPreCheckFailedException extends DomainException
{
    public static function openTellerSessions(int $count): static
    {
        return (new static("Masih ada {$count} sesi teller yang belum ditutup"))
            ->withContext(['open_sessions' => $count]);
    }
}
