<?php

use App\Actions\Deposit\PlaceDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\ApiClient;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;
use Illuminate\Support\Str;

function signDepositApiRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'dep-test-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Deposit API (Open API)', function (): void {

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

        $this->product = DepositProduct::factory()->create([
            'min_amount' => 1000000,
            'max_amount' => 2000000000,
        ]);

        DepositProductRate::create([
            'deposit_product_id' => $this->product->id,
            'tenor_months' => 12,
            'min_amount' => 1000000,
            'max_amount' => null,
            'interest_rate' => 6.0,
            'is_active' => true,
        ]);

        $this->account = app(PlaceDeposit::class)->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'dep-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/deposits/{accountNumber}', function (): void {

        it('shows deposit account detail', function (): void {
            $path = "api/v1/deposits/{$this->account->account_number}";
            $headers = signDepositApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/deposits/{$this->account->account_number}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number'],
                ]);
        });

        it('returns 404 for non-existent deposit account', function (): void {
            $headers = signDepositApiRequest('GET', 'api/v1/deposits/NONEXISTENT999', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/deposits/NONEXISTENT999');

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/deposits/{accountNumber}/transactions', function (): void {

        it('returns paginated deposit transactions', function (): void {
            $path = "api/v1/deposits/{$this->account->account_number}/transactions";
            $headers = signDepositApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/deposits/{$this->account->account_number}/transactions");

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

it('returns 401 for unauthenticated request to deposit endpoint', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/api/v1/deposits/ANYACCOUNT');

    $response->assertUnauthorized();
});
