<?php

namespace App\Exceptions\MobileBanking;

use App\Exceptions\DomainException;

class DeviceNotRegisteredException extends DomainException
{
    public static function unknownDevice(string $deviceId): self
    {
        return (new self('Perangkat tidak terdaftar.'))
            ->withContext([
                'device_id' => $deviceId,
            ]);
    }
}
