<?php

use App\Actions\MobileBanking\Auth\LoginMobileUser;
use App\Exceptions\MobileBanking\InvalidPinException;
use App\Exceptions\MobileBanking\MobileUserNotActiveException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('LoginMobileUser', function (): void {
    beforeEach(function (): void {
        $this->action = app(LoginMobileUser::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->mobileUser = MobileUser::factory()->create([
            'customer_id' => $this->customer->id,
            'phone_number' => '081234567890',
            'pin_hash' => Hash::make('123456'),
            'is_active' => true,
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);
    });

    it('successfully logs in with correct credentials and returns token and mobile_user', function (): void {
        $result = $this->action->execute('081234567890', '123456', 'iPhone 15');

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['token', 'mobile_user'])
            ->and($result['token'])->toBeString()->not->toBeEmpty()
            ->and($result['mobile_user'])->toBeInstanceOf(MobileUser::class)
            ->and($result['mobile_user']->id)->toBe($this->mobileUser->id);
    });

    it('throws InvalidPinException with wrong PIN', function (): void {
        $this->action->execute('081234567890', 'wrong_pin', 'iPhone 15');
    })->throws(InvalidPinException::class);

    it('throws MobileUserNotActiveException when user is inactive', function (): void {
        $this->mobileUser->update(['is_active' => false]);

        $this->action->execute('081234567890', '123456', 'iPhone 15');
    })->throws(MobileUserNotActiveException::class, 'Akun mobile banking telah dinonaktifkan.');

    it('throws InvalidPinException when PIN is locked', function (): void {
        $this->mobileUser->update([
            'pin_attempts' => 5,
            'pin_locked_until' => now()->addMinutes(30),
        ]);

        $this->action->execute('081234567890', '123456', 'iPhone 15');
    })->throws(InvalidPinException::class);

    it('increments pin_attempts on wrong PIN', function (): void {
        expect($this->mobileUser->pin_attempts)->toBe(0);

        try {
            $this->action->execute('081234567890', 'wrong_pin', 'iPhone 15');
        } catch (InvalidPinException) {
            // expected
        }

        $this->mobileUser->refresh();
        expect($this->mobileUser->pin_attempts)->toBe(1);
    });

    it('locks PIN after 5 failed attempts', function (): void {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->action->execute('081234567890', 'wrong_pin', 'iPhone 15');
            } catch (InvalidPinException) {
                // expected
            }
        }

        $this->mobileUser->refresh();
        expect($this->mobileUser->pin_attempts)->toBe(5)
            ->and($this->mobileUser->pin_locked_until)->not->toBeNull()
            ->and($this->mobileUser->isPinLocked())->toBeTrue();
    });

    it('resets pin_attempts on successful login', function (): void {
        $this->mobileUser->update(['pin_attempts' => 3]);

        $this->action->execute('081234567890', '123456', 'iPhone 15');

        $this->mobileUser->refresh();
        expect($this->mobileUser->pin_attempts)->toBe(0)
            ->and($this->mobileUser->pin_locked_until)->toBeNull();
    });

    it('updates last_login_at on successful login', function (): void {
        expect($this->mobileUser->last_login_at)->toBeNull();

        $this->action->execute('081234567890', '123456', 'iPhone 15');

        $this->mobileUser->refresh();
        expect($this->mobileUser->last_login_at)->not->toBeNull();
    });

    it('throws InvalidPinException when phone number not found', function (): void {
        $this->action->execute('089999999999', '123456', 'iPhone 15');
    })->throws(InvalidPinException::class);

    it('revokes old tokens and creates new token on login', function (): void {
        // Create an existing token
        $this->mobileUser->createToken('Old Device');
        expect($this->mobileUser->tokens()->count())->toBe(1);

        $result = $this->action->execute('081234567890', '123456', 'iPhone 15');

        // Old token deleted, new one created
        expect($this->mobileUser->tokens()->count())->toBe(1)
            ->and($result['token'])->toBeString()->not->toBeEmpty();
    });
});
