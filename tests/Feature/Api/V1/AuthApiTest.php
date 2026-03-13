<?php

use App\Models\ApiClient;
use Illuminate\Support\Str;

function signRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'test-client-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Open API Authentication (HMAC)', function (): void {

    beforeEach(function (): void {
        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'test-client-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('Missing headers', function (): void {

        it('returns 401 when all auth headers are missing', function (): void {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->get('/api/v1/branches');

            $response->assertUnauthorized()
                ->assertJsonPath('message', 'Missing authentication headers. Required: X-Client-Id, X-Timestamp, X-Signature');
        });

        it('returns 401 when X-Signature is missing', function (): void {
            $response = $this->withHeaders([
                'X-Client-Id' => 'test-client-001',
                'X-Timestamp' => now()->toIso8601String(),
                'Accept' => 'application/json',
            ])->get('/api/v1/branches');

            $response->assertUnauthorized();
        });
    });

    describe('Invalid credentials', function (): void {

        it('returns 401 for unknown client_id', function (): void {
            $timestamp = now()->toIso8601String();
            $bodyHash = hash('sha256', '');
            $stringToSign = "GET\napi/v1/branches\n{$timestamp}\n{$bodyHash}";
            $signature = hash_hmac('sha256', $stringToSign, 'fake-secret');

            $response = $this->withHeaders([
                'X-Client-Id' => 'unknown-client',
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
                'Accept' => 'application/json',
            ])->get('/api/v1/branches');

            $response->assertUnauthorized()
                ->assertJsonPath('message', 'Invalid client credentials.');
        });

        it('returns 401 for wrong signature', function (): void {
            $timestamp = now()->toIso8601String();

            $response = $this->withHeaders([
                'X-Client-Id' => 'test-client-001',
                'X-Timestamp' => $timestamp,
                'X-Signature' => 'invalid-signature',
                'Accept' => 'application/json',
            ])->get('/api/v1/branches');

            $response->assertUnauthorized()
                ->assertJsonPath('message', 'Invalid signature.');
        });
    });

    describe('Client status', function (): void {

        it('returns 403 for inactive client', function (): void {
            $this->apiClient->update(['is_active' => false]);

            $headers = signRequest('GET', 'api/v1/branches', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/branches');

            $response->assertForbidden()
                ->assertJsonPath('message', 'API client is inactive.');
        });
    });

    describe('IP restriction', function (): void {

        it('returns 403 when IP is not in allowed list', function (): void {
            $this->apiClient->update(['allowed_ips' => ['192.168.1.1']]);

            $headers = signRequest('GET', 'api/v1/branches', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/branches');

            $response->assertForbidden()
                ->assertJsonPath('message', 'IP address not allowed.');
        });
    });

    describe('Timestamp expiry', function (): void {

        it('returns 401 when timestamp is older than 5 minutes', function (): void {
            $timestamp = now()->subMinutes(6)->toIso8601String();
            $bodyHash = hash('sha256', '');
            $stringToSign = "GET\napi/v1/branches\n{$timestamp}\n{$bodyHash}";
            $signature = hash_hmac('sha256', $stringToSign, $this->secretKey);

            $response = $this->withHeaders([
                'X-Client-Id' => 'test-client-001',
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
                'Accept' => 'application/json',
            ])->get('/api/v1/branches');

            $response->assertUnauthorized()
                ->assertJsonPath('message', 'Request timestamp has expired.');
        });
    });

    describe('Successful authentication', function (): void {

        it('authenticates with valid HMAC signature', function (): void {
            $headers = signRequest('GET', 'api/v1/branches', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/branches');

            $response->assertOk();
        });

        it('updates last_used_at on successful request', function (): void {
            $headers = signRequest('GET', 'api/v1/branches', $this->secretKey);

            $this->withHeaders($headers)->get('/api/v1/branches');

            $this->apiClient->refresh();
            expect($this->apiClient->last_used_at)->not->toBeNull();
        });
    });
});
