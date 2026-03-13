<?php

namespace App\Actions\MobileBanking\Auth;

use App\Enums\OtpPurpose;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RequestOtp
{
    public function execute(string $phoneNumber, OtpPurpose $purpose, ?int $mobileUserId = null): OtpVerification
    {
        // Invalidate previous unused OTPs for same phone+purpose
        OtpVerification::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpVerification::create([
            'mobile_user_id' => $mobileUserId,
            'phone_number' => $phoneNumber,
            'otp_hash' => Hash::make($otpCode),
            'purpose' => $purpose,
            'is_used' => false,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        // TODO: Send OTP via SMS gateway
        Log::info('OTP generated', [
            'phone' => $phoneNumber,
            'purpose' => $purpose->value,
            'otp' => $otpCode, // Remove in production
        ]);

        return $otp;
    }
}
