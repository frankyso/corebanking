<?php

namespace App\Exceptions\MobileBanking;

use App\Exceptions\DomainException;

class MobileUserNotActiveException extends DomainException
{
    public static function blocked(): self
    {
        return new self('Akun mobile banking telah dinonaktifkan.');
    }

    public static function customerNotActive(): self
    {
        return new self('Status nasabah tidak aktif. Hubungi kantor cabang.');
    }

    public static function alreadyRegistered(): self
    {
        return new self('Nasabah sudah terdaftar di mobile banking.');
    }
}
