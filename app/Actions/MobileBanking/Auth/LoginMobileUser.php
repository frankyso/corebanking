<?php

namespace App\Actions\MobileBanking\Auth;

use App\Exceptions\MobileBanking\InvalidPinException;
use App\Exceptions\MobileBanking\MobileUserNotActiveException;
use App\Models\MobileUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class LoginMobileUser
{
    /**
     * @return array{token: string, mobile_user: MobileUser}
     */
    public function execute(string $phoneNumber, string $pin, string $deviceName): array
    {
        $mobileUser = MobileUser::where('phone_number', $phoneNumber)->first();

        if (! $mobileUser) {
            throw InvalidPinException::incorrectPin(0);
        }

        if (! $mobileUser->is_active) {
            throw MobileUserNotActiveException::blocked();
        }

        if ($mobileUser->isPinLocked()) {
            /** @var Carbon $lockedUntil */
            $lockedUntil = $mobileUser->pin_locked_until;
            throw InvalidPinException::pinLocked($lockedUntil);
        }

        if (! Hash::check($pin, $mobileUser->pin_hash)) {
            $mobileUser->incrementPinAttempts();
            $mobileUser->refresh();
            $remaining = max(0, 5 - $mobileUser->pin_attempts);

            throw InvalidPinException::incorrectPin($remaining);
        }

        $mobileUser->resetPinAttempts();
        $mobileUser->update(['last_login_at' => now()]);

        // Revoke old tokens (only keep current session)
        $mobileUser->tokens()->delete();
        $token = $mobileUser->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'mobile_user' => $mobileUser,
        ];
    }
}
