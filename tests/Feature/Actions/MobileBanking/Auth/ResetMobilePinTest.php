<?php

use App\Actions\MobileBanking\Auth\ResetMobilePin;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('ResetMobilePin', function (): void {
    beforeEach(function (): void {
        $this->action = app(ResetMobilePin::class);

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
            'pin_hash' => Hash::make('123456'),
            'pin_attempts' => 5,
            'pin_locked_until' => now()->addMinutes(30),
        ]);
    });

    it('successfully resets the PIN', function (): void {
        $this->action->execute($this->mobileUser, '654321');

        $this->mobileUser->refresh();

        expect(Hash::check('654321', $this->mobileUser->pin_hash))->toBeTrue()
            ->and(Hash::check('123456', $this->mobileUser->pin_hash))->toBeFalse();
    });

    it('resets pin_attempts and pin_locked_until', function (): void {
        $this->action->execute($this->mobileUser, '654321');

        $this->mobileUser->refresh();

        expect($this->mobileUser->pin_attempts)->toBe(0)
            ->and($this->mobileUser->pin_locked_until)->toBeNull();
    });

    it('revokes all tokens after reset', function (): void {
        // Create some tokens
        $this->mobileUser->createToken('Device 1');
        $this->mobileUser->createToken('Device 2');
        expect($this->mobileUser->tokens()->count())->toBe(2);

        $this->action->execute($this->mobileUser, '654321');

        expect($this->mobileUser->tokens()->count())->toBe(0);
    });

    it('hashes the new PIN', function (): void {
        $this->action->execute($this->mobileUser, '999999');

        $this->mobileUser->refresh();

        expect($this->mobileUser->pin_hash)->not->toBe('999999')
            ->and(Hash::check('999999', $this->mobileUser->pin_hash))->toBeTrue();
    });
});
