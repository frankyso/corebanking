<?php

namespace App\DTOs\MobileBanking;

readonly class RegisterDeviceData
{
    public function __construct(
        public string $deviceId,
        public string $deviceName,
        public string $platform,
        public ?string $fcmToken = null,
    ) {}
}
