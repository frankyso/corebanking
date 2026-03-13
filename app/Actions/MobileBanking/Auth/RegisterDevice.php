<?php

namespace App\Actions\MobileBanking\Auth;

use App\DTOs\MobileBanking\RegisterDeviceData;
use App\Models\MobileDevice;
use App\Models\MobileUser;

class RegisterDevice
{
    public function execute(MobileUser $mobileUser, RegisterDeviceData $dto): MobileDevice
    {
        return MobileDevice::updateOrCreate(
            [
                'mobile_user_id' => $mobileUser->id,
                'device_id' => $dto->deviceId,
            ],
            [
                'device_name' => $dto->deviceName,
                'platform' => $dto->platform,
                'fcm_token' => $dto->fcmToken,
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );
    }
}
