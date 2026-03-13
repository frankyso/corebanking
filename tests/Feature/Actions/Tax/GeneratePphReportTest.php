<?php

use App\Actions\Tax\GeneratePphReport;
use App\DTOs\Tax\PphReportData;
use App\DTOs\Tax\PphReportResult;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositInterestAccrual;
use App\Models\IndividualDetail;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use App\Models\User;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    $this->customer = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    $this->individualDetail = IndividualDetail::factory()->create([
        'customer_id' => $this->customer->id,
        'full_name' => 'John Doe',
        'npwp' => '12.345.678.9-012.345',
    ]);
});

it('returns empty result when no accruals exist', function (): void {
    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    expect($result)->toBeInstanceOf(PphReportResult::class)
        ->and($result->totalGrossInterest)->toBe(0.0)
        ->and($result->totalTax)->toBe(0.0)
        ->and($result->totalNetInterest)->toBe(0.0)
        ->and($result->customerCount)->toBe(0)
        ->and($result->customerBreakdown)->toBeEmpty();
});

it('calculates correct totals for deposit accruals', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-02-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-02-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    expect($result->totalGrossInterest)->toBe(100000.0)
        ->and($result->totalTax)->toBe(20000.0)
        ->and($result->totalNetInterest)->toBe(80000.0)
        ->and($result->customerCount)->toBe(1)
        ->and($result->customerBreakdown)->toHaveCount(1);

    $row = $result->customerBreakdown->first();
    expect($row['customer_name'])->toBe('John Doe')
        ->and($row['npwp'])->toBe('12.345.678.9-012.345')
        ->and($row['product_type'])->toBe('Deposito');
});

it('calculates correct totals for savings accruals', function (): void {
    $savingsAccount = SavingsAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    SavingsInterestAccrual::create([
        'savings_account_id' => $savingsAccount->id,
        'accrual_date' => '2026-03-10',
        'balance' => 500_000_000,
        'interest_rate' => 3.0,
        'accrued_amount' => 30000.00,
        'tax_amount' => 6000.00,
        'is_posted' => true,
        'posted_at' => '2026-03-10',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    expect($result->totalGrossInterest)->toBe(30000.0)
        ->and($result->totalTax)->toBe(6000.0)
        ->and($result->totalNetInterest)->toBe(24000.0)
        ->and($result->customerCount)->toBe(1);

    $row = $result->customerBreakdown->first();
    expect($row['product_type'])->toBe('Tabungan');
});

it('aggregates deposit and savings accruals per customer', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $savingsAccount = SavingsAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    SavingsInterestAccrual::create([
        'savings_account_id' => $savingsAccount->id,
        'accrual_date' => '2026-01-15',
        'balance' => 500_000_000,
        'interest_rate' => 3.0,
        'accrued_amount' => 30000.00,
        'tax_amount' => 6000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    expect($result->customerCount)->toBe(1)
        ->and($result->totalGrossInterest)->toBe(80000.0)
        ->and($result->totalTax)->toBe(16000.0)
        ->and($result->totalNetInterest)->toBe(64000.0);

    $row = $result->customerBreakdown->first();
    expect($row['product_type'])->toContain('Deposito')
        ->and($row['product_type'])->toContain('Tabungan');
});

it('filters by month', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-02-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 40000.00,
        'tax_amount' => 8000.00,
        'is_posted' => true,
        'posted_at' => '2026-02-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026, month: 1)
    );

    expect($result->totalGrossInterest)->toBe(50000.0)
        ->and($result->totalTax)->toBe(10000.0);
});

it('filters by product type', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $savingsAccount = SavingsAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    SavingsInterestAccrual::create([
        'savings_account_id' => $savingsAccount->id,
        'accrual_date' => '2026-01-15',
        'balance' => 500_000_000,
        'interest_rate' => 3.0,
        'accrued_amount' => 30000.00,
        'tax_amount' => 6000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $depositOnly = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026, productType: 'deposit')
    );

    expect($depositOnly->totalGrossInterest)->toBe(50000.0)
        ->and($depositOnly->totalTax)->toBe(10000.0);

    $savingsOnly = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026, productType: 'savings')
    );

    expect($savingsOnly->totalGrossInterest)->toBe(30000.0)
        ->and($savingsOnly->totalTax)->toBe(6000.0);
});

it('filters by branch', function (): void {
    $otherBranch = Branch::factory()->create();

    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026, branchId: $otherBranch->id)
    );

    expect($result->customerCount)->toBe(0)
        ->and($result->totalGrossInterest)->toBe(0.0);

    $resultCorrectBranch = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026, branchId: $this->branch->id)
    );

    expect($resultCorrectBranch->customerCount)->toBe(1)
        ->and($resultCorrectBranch->totalGrossInterest)->toBe(50000.0);
});

it('excludes accruals with zero tax', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 5000.00,
        'tax_amount' => 0.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    expect($result->customerCount)->toBe(0)
        ->and($result->totalGrossInterest)->toBe(0.0);
});

it('shows customer type labels correctly', function (): void {
    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => '2026-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => '2026-01-15',
    ]);

    $result = app(GeneratePphReport::class)->execute(
        new PphReportData(year: 2026)
    );

    $row = $result->customerBreakdown->first();
    expect($row['customer_type'])->toBe('Perorangan');
});
