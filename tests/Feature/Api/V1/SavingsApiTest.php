<?php

use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileDevice;
use App\Models\MobileUser;
use App\Models\SavingsProduct;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Savings API', function (): void {

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

        $this->product = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
        ]);

        $this->account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 1000000,
            performer: $this->user,
        ));

        Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');
    });

    describe('GET /api/v1/savings', function (): void {

        it('lists customer savings accounts', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson('/api/v1/savings');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['account_number'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(1);
        });

        it('returns empty array when customer has no accounts', function (): void {
            $otherCustomer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            $otherMobileUser = MobileUser::factory()->create([
                'customer_id' => $otherCustomer->id,
            ]);

            MobileDevice::factory()->create([
                'mobile_user_id' => $otherMobileUser->id,
                'device_id' => 'other-device',
                'is_active' => true,
            ]);

            Sanctum::actingAs($otherMobileUser, ['*'], 'mobile');

            $response = $this->withHeaders([
                'X-Device-Id' => 'other-device',
                'Accept' => 'application/json',
            ])->getJson('/api/v1/savings');

            $response->assertOk();
            expect($response->json('data'))->toBeEmpty();
        });
    });

    describe('GET /api/v1/savings/{accountNumber}', function (): void {

        it('shows account detail', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson("/api/v1/savings/{$this->account->account_number}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number'],
                ]);
        });

        it('returns 404 for non-existent account', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson('/api/v1/savings/NONEXISTENT999');

            $response->assertNotFound();
        });

        it('returns 404 when accessing another customer account', function (): void {
            $otherCustomer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            $otherAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
                product: $this->product,
                customerId: $otherCustomer->id,
                branchId: $this->branch->id,
                initialDeposit: 500000,
                performer: $this->user,
            ));

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson("/api/v1/savings/{$otherAccount->account_number}");

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/savings/{accountNumber}/balance', function (): void {

        it('returns balance data', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson("/api/v1/savings/{$this->account->account_number}/balance");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number', 'balance', 'hold_amount', 'available_balance'],
                ]);

            expect((float) $response->json('data.balance'))->toBe(1000000.0);
        });
    });

    describe('GET /api/v1/savings/{accountNumber}/transactions', function (): void {

        it('returns paginated transactions', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson("/api/v1/savings/{$this->account->account_number}/transactions");

            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta',
                ]);

            // Opening transaction should be present
            expect($response->json('data'))->toHaveCount(1);
        });

        it('returns 404 for another customer account transactions', function (): void {
            $otherCustomer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ]);

            $otherAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
                product: $this->product,
                customerId: $otherCustomer->id,
                branchId: $this->branch->id,
                initialDeposit: 500000,
                performer: $this->user,
            ));

            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->getJson("/api/v1/savings/{$otherAccount->account_number}/transactions");

            $response->assertNotFound();
        });
    });

});

it('returns 401 for unauthenticated request to savings list', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->getJson('/api/v1/savings');

    $response->assertUnauthorized();
});
