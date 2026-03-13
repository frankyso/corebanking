<?php

use App\Actions\MobileBanking\Auth\ChangeMobilePin;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('ChangeMobilePin', function (): void {
    beforeEach(function (): void {
        $this->action = app(ChangeMobilePin::class);

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
            'pin_attempts' => 3,
            'pin_locked_until' => now()->addMinutes(10),
        ]);
    });

    it('successfully changes the PIN', function (): void {
        $this->action->execute($this->mobileUser, '654321');

        $this->mobileUser->refresh();

        expect(Hash::check('654321', $this->mobileUser->pin_hash))->toBeTrue()
            ->and(Hash::check('123456', $this->mobileUser->pin_hash))->toBeFalse();
    });

    it('hashes the new PIN', function (): void {
        $this->action->execute($this->mobileUser, '654321');

        $this->mobileUser->refresh();

        expect($this->mobileUser->pin_hash)->not->toBe('654321')
            ->and(Hash::check('654321', $this->mobileUser->pin_hash))->toBeTrue();
    });

    it('resets pin_attempts and pin_locked_until', function (): void {
        expect($this->mobileUser->pin_attempts)->toBe(3)
            ->and($this->mobileUser->pin_locked_until)->not->toBeNull();

        $this->action->execute($this->mobileUser, '654321');

        $this->mobileUser->refresh();

        expect($this->mobileUser->pin_attempts)->toBe(0)
            ->and($this->mobileUser->pin_locked_until)->toBeNull();
    });
});
