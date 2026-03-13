<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileDevice;
use App\Models\MobileUser;
use App\Models\SavingsProduct;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('General API - public endpoints', function (): void {

    describe('GET /api/v1/system/app-info', function (): void {

        it('returns app info without authentication', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->getJson('/api/v1/system/app-info');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['app_name', 'version', 'minimum_version', 'maintenance_mode'],
                ]);
        });
    });
});

describe('General API - authenticated endpoints', function (): void {

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
        ]);

        MobileDevice::factory()->create([
            'mobile_user_id' => $this->mobileUser->id,
            'device_id' => 'test-device',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');
    });

    describe('GET /api/v1/branches', function (): void {

        it('returns list of active branches', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson('/api/v1/branches');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id'],
                    ],
                ]);
        });
    });

    describe('GET /api/v1/products/savings', function (): void {

        it('returns list of active savings products', function (): void {
            SavingsProduct::factory()->create(['is_active' => true]);

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson('/api/v1/products/savings');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id'],
                    ],
                ]);
        });
    });
});

it('returns 401 for unauthenticated request to branches', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->getJson('/api/v1/branches');

    $response->assertUnauthorized();
});

it('returns 401 for unauthenticated request to products/savings', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->getJson('/api/v1/products/savings');

    $response->assertUnauthorized();
});
