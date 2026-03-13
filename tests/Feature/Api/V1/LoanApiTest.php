<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Models\ApiClient;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Support\Str;

function signLoanApiRequest(string $method, string $path, string $secretKey, string $body = ''): array
{
    $timestamp = now()->toIso8601String();
    $bodyHash = hash('sha256', $body);
    $stringToSign = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
    $signature = hash_hmac('sha256', $stringToSign, $secretKey);

    return [
        'X-Client-Id' => 'loan-test-001',
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ];
}

describe('Loan API (Open API)', function (): void {

    beforeEach(function (): void {
        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = LoanProduct::factory()->create();

        $application = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 10000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));

        app(ApproveLoanApplication::class)->execute(new ApproveLoanApplicationData(
            application: $application,
            approver: $this->approver,
        ));

        $this->account = app(DisburseLoan::class)->execute($application->fresh(), $this->user);

        $this->secretKey = Str::random(64);
        $this->apiClient = ApiClient::create([
            'name' => 'Test Client',
            'client_id' => 'loan-test-001',
            'secret_key' => $this->secretKey,
            'is_active' => true,
            'rate_limit' => 60,
        ]);
    });

    describe('GET /api/v1/loans/{accountNumber}', function (): void {

        it('shows loan account detail', function (): void {
            $path = "api/v1/loans/{$this->account->account_number}";
            $headers = signLoanApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/loans/{$this->account->account_number}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['account_number'],
                ]);
        });

        it('returns 404 for non-existent loan account', function (): void {
            $headers = signLoanApiRequest('GET', 'api/v1/loans/NONEXISTENT999', $this->secretKey);

            $response = $this->withHeaders($headers)->get('/api/v1/loans/NONEXISTENT999');

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/loans/{accountNumber}/schedule', function (): void {

        it('returns amortization schedule', function (): void {
            $path = "api/v1/loans/{$this->account->account_number}/schedule";
            $headers = signLoanApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/loans/{$this->account->account_number}/schedule");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['installment_number', 'due_date'],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(12);
        });
    });

    describe('GET /api/v1/loans/{accountNumber}/payments', function (): void {

        it('returns empty payment history for new loan', function (): void {
            $path = "api/v1/loans/{$this->account->account_number}/payments";
            $headers = signLoanApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/loans/{$this->account->account_number}/payments");

            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta',
                ]);

            expect($response->json('data'))->toHaveCount(0);
        });
    });

    describe('GET /api/v1/loans/{accountNumber}/overdue', function (): void {

        it('returns overdue installments', function (): void {
            $path = "api/v1/loans/{$this->account->account_number}/overdue";
            $headers = signLoanApiRequest('GET', $path, $this->secretKey);

            $response = $this->withHeaders($headers)->get("/api/v1/loans/{$this->account->account_number}/overdue");

            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                ]);
        });
    });
});

it('returns 401 for unauthenticated request to loan endpoint', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->get('/api/v1/loans/ANYACCOUNT');

    $response->assertUnauthorized();
});
