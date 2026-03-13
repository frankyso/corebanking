<?php

namespace App\Actions\MobileBanking\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\MobileBanking\InvalidOtpException;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;

class VerifyOtp
{
    public function execute(string $phoneNumber, string $otpCode, OtpPurpose $purpose): OtpVerification
    {
        $otp = OtpVerification::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (! $otp) {
            throw InvalidOtpException::expired();
        }

        if ($otp->isExpired()) {
            throw InvalidOtpException::expired();
        }

        if (! Hash::check($otpCode, $otp->otp_hash)) {
            $otp->incrementAttempts();

            if ($otp->attempts >= 5) {
                $otp->markUsed();
                throw InvalidOtpException::expired();
            }

            throw InvalidOtpException::incorrectCode(5 - $otp->attempts);
        }

        $otp->markUsed();

        return $otp;
    }
}
