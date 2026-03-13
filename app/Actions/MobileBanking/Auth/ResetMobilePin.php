<?php

namespace App\Actions\MobileBanking\Auth;

use App\Models\MobileUser;
use Illuminate\Support\Facades\Hash;

class ResetMobilePin
{
    public function execute(MobileUser $mobileUser, string $newPin): void
    {
        $mobileUser->update([
            'pin_hash' => Hash::make($newPin),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);

        // Revoke all tokens for security
        $mobileUser->tokens()->delete();
    }
}
