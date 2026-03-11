<?php

namespace App\Exceptions\Teller;

use App\Exceptions\DomainException;
use App\Models\User;

class TellerSessionAlreadyOpenException extends DomainException
{
    public static function alreadyOpen(User $teller): static
    {
        return (new static('Teller sudah memiliki sesi aktif'))
            ->withContext([
                'teller_id' => $teller->id,
                'teller_name' => $teller->name,
            ]);
    }
}
