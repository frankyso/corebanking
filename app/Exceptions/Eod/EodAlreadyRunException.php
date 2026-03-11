<?php

namespace App\Exceptions\Eod;

use App\Exceptions\DomainException;
use Carbon\Carbon;

class EodAlreadyRunException extends DomainException
{
    public static function alreadyCompleted(Carbon $date): static
    {
        return (new static("EOD untuk tanggal {$date->format('d/m/Y')} sudah pernah dijalankan"))
            ->withContext(['date' => $date->toDateString()]);
    }

    public static function alreadyRunning(): static
    {
        return new static('EOD sedang berjalan');
    }
}
