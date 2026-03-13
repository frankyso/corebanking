<?php

namespace App\DTOs\MobileBanking;

readonly class RegisterMobileUserData
{
    public function __construct(
        public string $cifNumber,
        public string $phoneNumber,
        public string $pin,
    ) {}
}
