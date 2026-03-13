<?php

use App\Enums\CustomerStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileDevice;
use App\Models\MobileUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Auth API', function (): void {

    beforeEach(function (): void {
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
        ]);

        MobileDevice::factory()->create([
            'mobile_user_id' => $this->mobileUser->id,
            'device_id' => 'test-device',
            'is_active' => true,
        ]);
    });

    describe('POST /api/v1/auth/login', function (): void {

        it('returns token on successful login', function (): void {
            $response = $this->postJson('/api/v1/auth/login', [
                'phone_number' => '081234567890',
                'pin' => '123456',
                'device_id' => 'test-device',
                'device_name' => 'Samsung Galaxy S24',
                'platform' => 'android',
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['token', 'mobile_user'],
                    'message',
                ])
                ->assertJsonPath('message', 'Login berhasil.');
        });

        it('returns 422 with wrong PIN', function (): void {
            $response = $this->postJson('/api/v1/auth/login', [
                'phone_number' => '081234567890',
                'pin' => '999999',
                'device_id' => 'test-device',
                'device_name' => 'Samsung Galaxy S24',
                'platform' => 'android',
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'PIN salah. Sisa percobaan: 4.');
        });

        it('returns 422 for inactive mobile user', function (): void {
            $this->mobileUser->update(['is_active' => false]);

            $response = $this->postJson('/api/v1/auth/login', [
                'phone_number' => '081234567890',
                'pin' => '123456',
                'device_id' => 'test-device',
                'device_name' => 'Samsung Galaxy S24',
                'platform' => 'android',
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'Akun mobile banking telah dinonaktifkan.');
        });

        it('returns 422 for validation errors when fields are missing', function (): void {
            $response = $this->postJson('/api/v1/auth/login', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number', 'pin', 'device_id', 'device_name', 'platform']);
        });
    });

    describe('POST /api/v1/auth/logout', function (): void {

        it('deletes token on logout', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertOk()
                ->assertJsonPath('message', 'Logout berhasil.');
        });

        it('returns 401 for unauthenticated request', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertUnauthorized();
        });
    });

    describe('POST /api/v1/auth/otp/request', function (): void {

        it('sends OTP successfully', function (): void {
            $response = $this->postJson('/api/v1/auth/otp/request', [
                'phone_number' => '081234567890',
                'purpose' => 'registration',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Kode OTP telah dikirim.');
        });

        it('returns 422 for invalid purpose', function (): void {
            $response = $this->postJson('/api/v1/auth/otp/request', [
                'phone_number' => '081234567890',
                'purpose' => 'invalid_purpose',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['purpose']);
        });
    });

    describe('POST /api/v1/auth/pin/change', function (): void {

        it('changes PIN with correct current PIN in header', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/pin/change', [
                'new_pin' => '654321',
                'new_pin_confirmation' => '654321',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'PIN berhasil diubah.');
        });

        it('returns 422 when PIN header is missing', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/pin/change', [
                'new_pin' => '654321',
                'new_pin_confirmation' => '654321',
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'PIN transaksi diperlukan.');
        });

        it('returns 403 when PIN is wrong', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '000000',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/pin/change', [
                'new_pin' => '654321',
                'new_pin_confirmation' => '654321',
            ]);

            $response->assertForbidden()
                ->assertJsonStructure(['message', 'remaining_attempts']);
        });
    });

    describe('POST /api/v1/auth/device/register', function (): void {

        it('registers a new device', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/device/register', [
                'device_id' => 'new-device-id',
                'device_name' => 'iPhone 15 Pro',
                'platform' => 'ios',
            ]);

            $response->assertCreated()
                ->assertJsonPath('message', 'Perangkat berhasil didaftarkan.');
        });

        it('returns 422 when required fields are missing', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/device/register', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_id', 'device_name', 'platform']);
        });
    });

    describe('middleware checks', function (): void {

        it('returns 403 when customer is not active', function (): void {
            $this->customer->update(['status' => CustomerStatus::Blocked]);
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertForbidden()
                ->assertJsonPath('message', 'Status nasabah tidak aktif.');
        });

        it('returns 403 when mobile user is not active', function (): void {
            $this->mobileUser->update(['is_active' => false]);
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertForbidden()
                ->assertJsonPath('message', 'Akun mobile banking tidak aktif.');
        });

        it('returns 422 when X-Device-Id header is missing', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertStatus(422)
                ->assertJsonPath('message', 'Device ID diperlukan.');
        });

        it('returns 403 when device is not registered', function (): void {
            Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'unknown-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/auth/logout');

            $response->assertForbidden()
                ->assertJsonPath('message', 'Perangkat tidak terdaftar. Silakan daftarkan perangkat terlebih dahulu.');
        });
    });
});
