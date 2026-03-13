<?php

use App\Models\ApiClient;
use App\Models\Branch;
use App\Models\SavingsProduct;
use Illuminate\Support\Str;

function signApiRequest(string $method, string $path, string $clientId, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => $clientId,
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('General API - public endpoints', function (): void {

    describe('GET /api/v1/system/app-info', function (): void {

        it('returns app info without authentication', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->get('/api/v1/system/app-info');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['app_name', 'version', 'maintenance_mode'],
                ]);
        });

        it('does not include minimum_version', function (): void {
            $response = $this->get('/api/v1/system/app-info');

            $response->assertOk()
                ->assertJsonMissing(['minimum_version']);
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

        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'gen-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/branches', function (): void {

        it('returns list of active branches', function (): void {
            $headers = signApiRequest('GET', 'api/v1/branches', 'gen-test-001', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/branches');

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

            $headers = signApiRequest('GET', 'api/v1/products/savings', 'gen-test-001', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/products/savings');

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
    ])->get('/api/v1/branches');

    $response->assertUnauthorized();
});

it('returns 401 for unauthenticated request to products/savings', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/api/v1/products/savings');

    $response->assertUnauthorized();
});
