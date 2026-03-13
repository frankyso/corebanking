<?php

use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Models\ApiClient;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Support\Str;

function signSavingsApiRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'sav-test-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Savings API (Open API)', function (): void {

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

        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'sav-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/savings/{accountNumber}', function (): void {

        it('shows account detail', function (): void {
            $path = "api/v1/savings/{$this->account->account_number}";
            $headers = signSavingsApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/savings/{$this->account->account_number}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number'],
                ]);
        });

        it('returns 404 for non-existent account', function (): void {
            $headers = signSavingsApiRequest('GET', 'api/v1/savings/NONEXISTENT999', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/savings/NONEXISTENT999');

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/savings/{accountNumber}/balance', function (): void {

        it('returns balance data', function (): void {
            $path = "api/v1/savings/{$this->account->account_number}/balance";
            $headers = signSavingsApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/savings/{$this->account->account_number}/balance");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number', 'balance', 'hold_amount', 'available_balance'],
                ]);

            expect((float) $response->json('data.balance'))->toBe(1000000.0);
        });
    });

    describe('GET /api/v1/savings/{accountNumber}/transactions', function (): void {

        it('returns paginated transactions', function (): void {
            $path = "api/v1/savings/{$this->account->account_number}/transactions";
            $headers = signSavingsApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/savings/{$this->account->account_number}/transactions");

            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta',
                ]);

            expect($response->json('data'))->toHaveCount(1);
        });
    });
});

it('returns 401 for unauthenticated request to savings endpoint', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/api/v1/savings/ANYACCOUNT');

    $response->assertUnauthorized();
});
