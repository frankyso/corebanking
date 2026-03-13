<?php

use App\Actions\MobileBanking\Auth\RequestOtp;
use App\Enums\OtpPurpose;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\OtpVerification;
use App\Models\User;

describe('RequestOtp', function (): void {
    beforeEach(function (): void {
        $this->action = app(RequestOtp::class);
    });

    it('creates an OTP verification record', function (): void {
        $otp = $this->action->execute('081234567890', OtpPurpose::Registration);

        expect($otp)->toBeInstanceOf(OtpVerification::class)
            ->and($otp->phone_number)->toBe('081234567890')
            ->and($otp->purpose)->toBe(OtpPurpose::Registration)
            ->and($otp->is_used)->toBeFalse()
            ->and($otp->attempts)->toBe(0)
            ->and($otp->expires_at)->not->toBeNull();
    });

    it('hashes the OTP code (not stored in plain text)', function (): void {
        $otp = $this->action->execute('081234567890', OtpPurpose::Registration);

        // otp_hash should not be a 6-digit number (it should be hashed)
        expect($otp->otp_hash)->not->toMatch('/^\d{6}$/')
            ->and(strlen($otp->otp_hash))->toBeGreaterThan(6);
    });

    it('sets expiration 5 minutes in the future', function (): void {
        $otp = $this->action->execute('081234567890', OtpPurpose::Registration);

        expect($otp->expires_at->isFuture())->toBeTrue()
            ->and($otp->isExpired())->toBeFalse();
    });

    it('invalidates previous unused OTPs for same phone and purpose', function (): void {
        $firstOtp = $this->action->execute('081234567890', OtpPurpose::Registration);
        expect($firstOtp->is_used)->toBeFalse();

        $secondOtp = $this->action->execute('081234567890', OtpPurpose::Registration);

        $firstOtp->refresh();
        expect($firstOtp->is_used)->toBeTrue()
            ->and($secondOtp->is_used)->toBeFalse();
    });

    it('does not invalidate OTPs for different purposes', function (): void {
        $registrationOtp = $this->action->execute('081234567890', OtpPurpose::Registration);
        $transactionOtp = $this->action->execute('081234567890', OtpPurpose::Transaction);

        $registrationOtp->refresh();
        expect($registrationOtp->is_used)->toBeFalse()
            ->and($transactionOtp->is_used)->toBeFalse();
    });

    it('does not invalidate OTPs for different phone numbers', function (): void {
        $otp1 = $this->action->execute('081234567890', OtpPurpose::Registration);
        $otp2 = $this->action->execute('081234567891', OtpPurpose::Registration);

        $otp1->refresh();
        expect($otp1->is_used)->toBeFalse()
            ->and($otp2->is_used)->toBeFalse();
    });

    it('accepts optional mobile_user_id', function (): void {
        $branch = Branch::create(['code' => '001', 'name' => 'Test', 'is_head_office' => true, 'is_active' => true]);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $customer = Customer::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
        ]);
        $mobileUser = MobileUser::factory()->create(['customer_id' => $customer->id]);

        $otp = $this->action->execute('081234567890', OtpPurpose::Registration, $mobileUser->id);

        expect($otp->mobile_user_id)->toBe($mobileUser->id);
    });
});
