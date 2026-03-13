<?php

use App\Models\ApiClient;
use App\Models\DepositProduct;
use App\Models\LoanProduct;
use App\Models\SavingsProduct;
use Illuminate\Support\Str;

function signProductApiRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'prod-test-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Product API (Open API)', function (): void {

    beforeEach(function (): void {
        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'prod-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/products/savings', function (): void {

        it('returns active savings products', function (): void {
            SavingsProduct::factory()->count(2)->create();
            SavingsProduct::factory()->create(['is_active' => false]);

            $headers = signProductApiRequest('GET', 'api/v1/products/savings', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/products/savings');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['code', 'name'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(2);
        });
    });

    describe('GET /api/v1/products/deposits', function (): void {

        it('returns active deposit products', function (): void {
            DepositProduct::factory()->count(2)->create();
            DepositProduct::factory()->create(['is_active' => false]);

            $headers = signProductApiRequest('GET', 'api/v1/products/deposits', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/products/deposits');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['code', 'name'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(2);
        });
    });

    describe('GET /api/v1/products/loans', function (): void {

        it('returns active loan products', function (): void {
            LoanProduct::factory()->count(2)->create();
            LoanProduct::factory()->create(['is_active' => false]);

            $headers = signProductApiRequest('GET', 'api/v1/products/loans', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/products/loans');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['code', 'name'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(2);
        });
    });
});

it('returns 401 for unauthenticated request to product endpoint', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/api/v1/products/savings');

    $response->assertUnauthorized();
});
