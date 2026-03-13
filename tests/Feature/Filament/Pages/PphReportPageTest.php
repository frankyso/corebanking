<?php

use App\DTOs\Tax\PphReportResult;
use App\Filament\Pages\PphReportPage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositInterestAccrual;
use App\Models\IndividualDetail;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
    $this->actingAs($this->user);
});

it('can render PPh report page', function (): void {
    Livewire::test(PphReportPage::class)
        ->assertOk();
});

it('initializes with current year', function (): void {
    Livewire::test(PphReportPage::class)
        ->assertSet('year', (int) now()->year)
        ->assertSet('month', 0)
        ->assertSet('productType', '')
        ->assertSet('branchId', null);
});

it('report computed property returns PphReportResult', function (): void {
    $component = Livewire::test(PphReportPage::class);
    $report = $component->instance()->report;

    expect($report)->toBeInstanceOf(PphReportResult::class)
        ->and($report->totalGrossInterest)->toBeFloat()
        ->and($report->totalTax)->toBeFloat()
        ->and($report->totalNetInterest)->toBeFloat()
        ->and($report->customerCount)->toBeInt();
});

it('shows data when accruals exist', function (): void {
    $customer = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    IndividualDetail::factory()->create([
        'customer_id' => $customer->id,
        'full_name' => 'Test Customer',
        'npwp' => '00.000.000.0-000.000',
    ]);

    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => now()->format('Y').'-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => now()->format('Y').'-01-15',
    ]);

    $component = Livewire::test(PphReportPage::class);
    $report = $component->instance()->report;

    expect($report->customerCount)->toBe(1)
        ->and($report->totalGrossInterest)->toBe(50000.0)
        ->and($report->totalTax)->toBe(10000.0);
});

it('filters by month', function (): void {
    $customer = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    IndividualDetail::factory()->create([
        'customer_id' => $customer->id,
    ]);

    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => now()->format('Y').'-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => now()->format('Y').'-01-15',
    ]);

    $component = Livewire::test(PphReportPage::class)
        ->set('month', 2);

    $report = $component->instance()->report;
    expect($report->customerCount)->toBe(0);

    $component2 = Livewire::test(PphReportPage::class)
        ->set('month', 1);

    $report2 = $component2->instance()->report;
    expect($report2->customerCount)->toBe(1);
});

it('filters by product type', function (): void {
    $customer = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    IndividualDetail::factory()->create([
        'customer_id' => $customer->id,
    ]);

    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => now()->format('Y').'-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => now()->format('Y').'-01-15',
    ]);

    $component = Livewire::test(PphReportPage::class)
        ->set('productType', 'savings');

    $report = $component->instance()->report;
    expect($report->customerCount)->toBe(0);

    $component2 = Livewire::test(PphReportPage::class)
        ->set('productType', 'deposit');

    $report2 = $component2->instance()->report;
    expect($report2->customerCount)->toBe(1);
});

it('filters by branch', function (): void {
    $customer = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    IndividualDetail::factory()->create([
        'customer_id' => $customer->id,
    ]);

    $depositAccount = DepositAccount::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    DepositInterestAccrual::create([
        'deposit_account_id' => $depositAccount->id,
        'accrual_date' => now()->format('Y').'-01-15',
        'principal' => 100_000_000,
        'interest_rate' => 5.0,
        'accrued_amount' => 50000.00,
        'tax_amount' => 10000.00,
        'is_posted' => true,
        'posted_at' => now()->format('Y').'-01-15',
    ]);

    $otherBranch = Branch::factory()->create();

    $component = Livewire::test(PphReportPage::class)
        ->set('branchId', $otherBranch->id);

    $report = $component->instance()->report;
    expect($report->customerCount)->toBe(0);

    $component2 = Livewire::test(PphReportPage::class)
        ->set('branchId', $this->branch->id);

    $report2 = $component2->instance()->report;
    expect($report2->customerCount)->toBe(1);
});

it('exports CSV', function (): void {
    $component = Livewire::test(PphReportPage::class);

    $response = $component->instance()->exportCsv();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Disposition'))->toContain('laporan-pph-');
});

it('branches computed returns active branches', function (): void {
    Branch::factory()->create(['is_active' => true, 'name' => 'Branch A']);
    Branch::factory()->create(['is_active' => false, 'name' => 'Branch Inactive']);

    $component = Livewire::test(PphReportPage::class);
    $branches = $component->instance()->branches;

    $branchNames = $branches->values()->toArray();
    expect($branchNames)->toContain('Branch A')
        ->and($branchNames)->not->toContain('Branch Inactive');
});
