<?php

use App\Actions\MobileBanking\Auth\RegisterDevice;
use App\DTOs\MobileBanking\RegisterDeviceData;
use App\Enums\DevicePlatform;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileDevice;
use App\Models\MobileUser;
use App\Models\User;

describe('RegisterDevice', function (): void {
    beforeEach(function (): void {
        $this->action = app(RegisterDevice::class);

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
        ]);
    });

    it('creates a new device record', function (): void {
        $dto = new RegisterDeviceData(
            deviceId: 'device-uuid-123',
            deviceName: 'Samsung Galaxy S24',
            platform: 'android',
            fcmToken: 'fcm-token-abc',
        );

        $device = $this->action->execute($this->mobileUser, $dto);

        expect($device)->toBeInstanceOf(MobileDevice::class)
            ->and($device->mobile_user_id)->toBe($this->mobileUser->id)
            ->and($device->device_id)->toBe('device-uuid-123')
            ->and($device->device_name)->toBe('Samsung Galaxy S24')
            ->and($device->platform)->toBe(DevicePlatform::Android)
            ->and($device->fcm_token)->toBe('fcm-token-abc')
            ->and($device->is_active)->toBeTrue()
            ->and($device->last_used_at)->not->toBeNull();
    });

    it('updates existing device with same device_id', function (): void {
        $existingDevice = MobileDevice::factory()->create([
            'mobile_user_id' => $this->mobileUser->id,
            'device_id' => 'device-uuid-123',
            'device_name' => 'Old Device Name',
            'platform' => DevicePlatform::Android,
            'fcm_token' => 'old-fcm-token',
        ]);

        $dto = new RegisterDeviceData(
            deviceId: 'device-uuid-123',
            deviceName: 'Samsung Galaxy S24 Ultra',
            platform: 'android',
            fcmToken: 'new-fcm-token',
        );

        $device = $this->action->execute($this->mobileUser, $dto);

        expect($device->id)->toBe($existingDevice->id)
            ->and($device->device_name)->toBe('Samsung Galaxy S24 Ultra')
            ->and($device->fcm_token)->toBe('new-fcm-token')
            ->and(MobileDevice::where('mobile_user_id', $this->mobileUser->id)->count())->toBe(1);
    });

    it('creates separate device records for different device_ids', function (): void {
        $dto1 = new RegisterDeviceData(
            deviceId: 'device-uuid-1',
            deviceName: 'iPhone 15',
            platform: 'ios',
        );

        $dto2 = new RegisterDeviceData(
            deviceId: 'device-uuid-2',
            deviceName: 'Samsung Galaxy S24',
            platform: 'android',
        );

        $device1 = $this->action->execute($this->mobileUser, $dto1);
        $device2 = $this->action->execute($this->mobileUser, $dto2);

        expect($device1->id)->not->toBe($device2->id)
            ->and(MobileDevice::where('mobile_user_id', $this->mobileUser->id)->count())->toBe(2);
    });

    it('creates device without fcm_token', function (): void {
        $dto = new RegisterDeviceData(
            deviceId: 'device-uuid-123',
            deviceName: 'iPhone 15',
            platform: 'ios',
        );

        $device = $this->action->execute($this->mobileUser, $dto);

        expect($device->fcm_token)->toBeNull()
            ->and($device->platform)->toBe(DevicePlatform::Ios);
    });
});
