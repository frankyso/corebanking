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

describe('Transfer API', function (): void {

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

        $this->sourceAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 5000000,
            performer: $this->user,
        ));

        // Second account for own-account transfers
        $this->ownDestination = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        // Another customer for internal transfers
        $this->otherCustomer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->otherAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->otherCustomer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        Sanctum::actingAs($this->mobileUser, ['*'], 'mobile');
    });

    describe('POST /api/v1/transfers/validate', function (): void {

        it('validates an existing destination account', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/validate', [
                'destination_account_number' => $this->otherAccount->account_number,
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number', 'account_name'],
                ]);
        });

        it('returns 404 for non-existent destination account', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/validate', [
                'destination_account_number' => 'NONEXISTENT999',
            ]);

            $response->assertNotFound()
                ->assertJsonPath('message', 'Rekening tujuan tidak ditemukan.');
        });

        it('returns 422 when destination_account_number is missing', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/validate', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['destination_account_number']);
        });
    });

    describe('POST /api/v1/transfers/own-account', function (): void {

        it('transfers between own accounts with correct PIN', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/own-account', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->ownDestination->account_number,
                'amount' => 100000,
                'description' => 'Transfer sendiri',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Transfer berhasil.')
                ->assertJsonStructure([
                    'data',
                    'message',
                ]);

            // Verify balances changed
            $this->sourceAccount->refresh();
            $this->ownDestination->refresh();
            expect((float) $this->sourceAccount->balance)->toBe(4900000.0)
                ->and((float) $this->ownDestination->balance)->toBe(200000.0);
        });

        it('returns 422 when destination is not own account', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/own-account', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->otherAccount->account_number,
                'amount' => 100000,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'Rekening tujuan bukan milik Anda.');
        });

        it('returns 422 when PIN header is missing', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/own-account', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->ownDestination->account_number,
                'amount' => 100000,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'PIN transaksi diperlukan.');
        });

        it('returns 403 when PIN is wrong', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '999999',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/own-account', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->ownDestination->account_number,
                'amount' => 100000,
            ]);

            $response->assertForbidden()
                ->assertJsonStructure(['message', 'remaining_attempts']);
        });

        it('returns 422 when amount is below minimum', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/own-account', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->ownDestination->account_number,
                'amount' => 500,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
        });
    });

    describe('POST /api/v1/transfers/internal', function (): void {

        it('transfers to another customer account with correct PIN', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/internal', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->otherAccount->account_number,
                'amount' => 250000,
                'description' => 'Pembayaran',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Transfer berhasil.');

            // Verify balances
            $this->sourceAccount->refresh();
            $this->otherAccount->refresh();
            expect((float) $this->sourceAccount->balance)->toBe(4750000.0)
                ->and((float) $this->otherAccount->balance)->toBe(350000.0);
        });

        it('returns 422 when PIN header is missing', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/internal', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->otherAccount->account_number,
                'amount' => 100000,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'PIN transaksi diperlukan.');
        });

        it('returns 403 when PIN is wrong', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '000000',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/internal', [
                'source_account_number' => $this->sourceAccount->account_number,
                'destination_account_number' => $this->otherAccount->account_number,
                'amount' => 100000,
            ]);

            $response->assertForbidden()
                ->assertJsonStructure(['message', 'remaining_attempts']);
        });

        it('returns 422 when required fields are missing', function (): void {
            $response = $this->withHeaders([
                'X-Device-Id' => 'test-device',
                'X-Transaction-Pin' => '123456',
                'Accept' => 'application/json',
            ])->postJson('/api/v1/transfers/internal', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['source_account_number', 'destination_account_number', 'amount']);
        });
    });
});
