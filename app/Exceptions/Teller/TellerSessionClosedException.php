<?php

namespace App\Exceptions\Teller;

use App\Exceptions\DomainException;
use App\Models\TellerSession;

class TellerSessionClosedException extends DomainException
{
    public static function notOpen(TellerSession $session): static
    {
        return (new static('Sesi teller tidak aktif'))
            ->withContext([
                'session_id' => $session->id,
                'status' => $session->status->value,
            ]);
    }

    public static function alreadyClosed(TellerSession $session): static
    {
        return (new static('Sesi sudah ditutup'))
            ->withContext([
                'session_id' => $session->id,
                'closed_at' => $session->closed_at?->toIso8601String(),
            ]);
    }
}
