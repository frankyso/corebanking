<?php

use App\Actions\MobileBanking\Auth\RegisterMobileUser;
use App\DTOs\MobileBanking\RegisterMobileUserData;
use App\Enums\CustomerStatus;
use App\Exceptions\MobileBanking\MobileUserNotActiveException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('RegisterMobileUser', function (): void {
    beforeEach(function (): void {
        $this->action = app(RegisterMobileUser::class);

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
            'status' => CustomerStatus::Active,
        ]);
    });

    it('successfully registers a mobile user with valid CIF', function (): void {
        $dto = new RegisterMobileUserData(
            cifNumber: $this->customer->cif_number,
            phoneNumber: '081234567890',
            pin: '123456',
        );

        $mobileUser = $this->action->execute($dto);

        expect($mobileUser)->toBeInstanceOf(MobileUser::class)
            ->and($mobileUser->customer_id)->toBe($this->customer->id)
            ->and($mobileUser->phone_number)->toBe('081234567890')
            ->and($mobileUser->is_active)->toBeTrue()
            ->and(Hash::check('123456', $mobileUser->pin_hash))->toBeTrue();
    });

    it('throws exception when CIF not found', function (): void {
        $dto = new RegisterMobileUserData(
            cifNumber: 'NONEXISTENT',
            phoneNumber: '081234567890',
            pin: '123456',
        );

        $this->action->execute($dto);
    })->throws(MobileUserNotActiveException::class, 'Status nasabah tidak aktif. Hubungi kantor cabang.');

    it('throws exception when customer status is not active', function (): void {
        $this->customer->update(['status' => CustomerStatus::Blocked]);

        $dto = new RegisterMobileUserData(
            cifNumber: $this->customer->cif_number,
            phoneNumber: '081234567890',
            pin: '123456',
        );

        $this->action->execute($dto);
    })->throws(MobileUserNotActiveException::class, 'Status nasabah tidak aktif. Hubungi kantor cabang.');

    it('throws exception when customer already has a mobile user', function (): void {
        MobileUser::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $dto = new RegisterMobileUserData(
            cifNumber: $this->customer->cif_number,
            phoneNumber: '081234567890',
            pin: '123456',
        );

        $this->action->execute($dto);
    })->throws(MobileUserNotActiveException::class, 'Nasabah sudah terdaftar di mobile banking.');

    it('hashes the PIN before storing', function (): void {
        $dto = new RegisterMobileUserData(
            cifNumber: $this->customer->cif_number,
            phoneNumber: '081234567890',
            pin: '654321',
        );

        $mobileUser = $this->action->execute($dto);

        expect($mobileUser->pin_hash)->not->toBe('654321')
            ->and(Hash::check('654321', $mobileUser->pin_hash))->toBeTrue();
    });
});
