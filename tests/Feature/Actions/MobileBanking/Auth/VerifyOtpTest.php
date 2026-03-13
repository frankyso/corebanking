<?php

use App\Actions\MobileBanking\Auth\VerifyOtp;
use App\Enums\OtpPurpose;
use App\Exceptions\MobileBanking\InvalidOtpException;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;

describe('VerifyOtp', function (): void {
    beforeEach(function (): void {
        $this->action = app(VerifyOtp::class);
    });

    it('successfully verifies a valid OTP', function (): void {
        $otpCode = '123456';
        $otp = OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make($otpCode),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->action->execute('081234567890', $otpCode, OtpPurpose::Registration);

        expect($result)->toBeInstanceOf(OtpVerification::class)
            ->and($result->id)->toBe($otp->id)
            ->and($result->is_used)->toBeTrue();
    });

    it('throws exception for expired OTP', function (): void {
        OtpVerification::factory()->expired()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('123456'),
            'purpose' => OtpPurpose::Registration,
        ]);

        $this->action->execute('081234567890', '123456', OtpPurpose::Registration);
    })->throws(InvalidOtpException::class, 'Kode OTP sudah kadaluarsa.');

    it('throws exception for used OTP', function (): void {
        OtpVerification::factory()->used()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('123456'),
            'purpose' => OtpPurpose::Registration,
        ]);

        $this->action->execute('081234567890', '123456', OtpPurpose::Registration);
    })->throws(InvalidOtpException::class, 'Kode OTP sudah kadaluarsa.');

    it('throws exception for wrong OTP code', function (): void {
        OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('123456'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->action->execute('081234567890', '999999', OtpPurpose::Registration);
    })->throws(InvalidOtpException::class);

    it('increments attempts on wrong OTP code', function (): void {
        $otp = OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('123456'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        try {
            $this->action->execute('081234567890', '999999', OtpPurpose::Registration);
        } catch (InvalidOtpException) {
            // expected
        }

        $otp->refresh();
        expect($otp->attempts)->toBe(1);
    });

    it('marks OTP as used after 5 failed attempts', function (): void {
        $otp = OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('123456'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'attempts' => 4,
            'expires_at' => now()->addMinutes(5),
        ]);

        try {
            $this->action->execute('081234567890', '999999', OtpPurpose::Registration);
        } catch (InvalidOtpException) {
            // expected
        }

        $otp->refresh();
        expect($otp->attempts)->toBe(5)
            ->and($otp->is_used)->toBeTrue();
    });

    it('throws expired exception when no OTP record exists', function (): void {
        $this->action->execute('081234567890', '123456', OtpPurpose::Registration);
    })->throws(InvalidOtpException::class, 'Kode OTP sudah kadaluarsa.');

    it('verifies the latest OTP when multiple exist', function (): void {
        // Create an older OTP
        OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('111111'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now()->subMinutes(2),
        ]);

        // Create a newer OTP
        $latestOtp = OtpVerification::factory()->create([
            'phone_number' => '081234567890',
            'otp_hash' => Hash::make('222222'),
            'purpose' => OtpPurpose::Registration,
            'is_used' => false,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        $result = $this->action->execute('081234567890', '222222', OtpPurpose::Registration);

        expect($result->id)->toBe($latestOtp->id);
    });
});
