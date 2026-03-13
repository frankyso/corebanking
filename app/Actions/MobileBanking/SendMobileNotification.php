<?php

namespace App\Actions\MobileBanking;

use App\Enums\NotificationType;
use App\Models\MobileNotification;
use App\Models\MobileUser;

class SendMobileNotification
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function execute(
        MobileUser $mobileUser,
        string $title,
        string $body,
        NotificationType $type = NotificationType::Transaction,
        ?array $data = null,
    ): MobileNotification {
        return MobileNotification::create([
            'mobile_user_id' => $mobileUser->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);
    }
}
