<?php

use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Models\ApiClient;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Support\Str;

function signCustomerApiRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'cust-test-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Customer API (Open API)', function (): void {

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
            'client_id' => 'cust-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/customers/{cif}', function (): void {

        it('returns customer profile by CIF', function (): void {
            $cif = $this->customer->cif_number;
            $path = "api/v1/customers/{$cif}";
            $headers = signCustomerApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/customers/{$cif}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['cif_number', 'customer_type', 'status', 'display_name'],
                ]);
        });

        it('returns 404 for non-existent CIF', function (): void {
            $headers = signCustomerApiRequest('GET', 'api/v1/customers/NONEXISTENT', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/customers/NONEXISTENT');

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/customers/{cif}/savings', function (): void {

        it('returns savings accounts for a customer', function (): void {
            $cif = $this->customer->cif_number;
            $path = "api/v1/customers/{$cif}/savings";
            $headers = signCustomerApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/customers/{$cif}/savings");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['account_number'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(1);
        });
    });
});
