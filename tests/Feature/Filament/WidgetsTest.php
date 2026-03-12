<?php

use App\Enums\Collectibility;
use App\Enums\JournalStatus;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Enums\SavingsAccountStatus;
use App\Filament\Widgets\BankOverviewWidget;
use App\Filament\Widgets\LoanPortfolioChart;
use App\Filament\Widgets\NplRatioWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\JournalEntry;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\SavingsAccount;
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

    $this->customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);
});

// ─── BankOverviewWidget ──────────────────────────────────────────────────────

it('can render bank overview widget', function (): void {
    Livewire::test(BankOverviewWidget::class)
        ->assertOk();
});

it('bank overview widget returns four stats', function (): void {
    $stats = Livewire::test(BankOverviewWidget::class)
        ->invade()->getStats();

    expect($stats)->toHaveCount(4);
});

it('bank overview widget reflects active account data', function (): void {
    SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => SavingsAccountStatus::Active,
        'balance' => 10_000_000,
    ]);

    DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'principal_amount' => 50_000_000,
    ]);

    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 25_000_000,
    ]);

    $stats = Livewire::test(BankOverviewWidget::class)
        ->invade()->getStats();

    expect($stats)->toHaveCount(4)
        ->and($stats[0]->getLabel())->toBe('Outstanding Kredit')
        ->and($stats[1]->getLabel())->toBe('Total Tabungan')
        ->and($stats[2]->getLabel())->toBe('Total Deposito')
        ->and($stats[3]->getLabel())->toBe('Baki Debet');
});

it('bank overview widget excludes closed accounts', function (): void {
    SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => SavingsAccountStatus::Closed,
        'balance' => 99_000_000,
    ]);

    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Closed,
        'outstanding_principal' => 99_000_000,
    ]);

    $stats = Livewire::test(BankOverviewWidget::class)
        ->invade()->getStats();

    // Closed accounts should not be counted, so descriptions should show "0 rekening aktif"
    expect($stats[0]->getDescription())->toContain('0 rekening aktif')
        ->and($stats[1]->getDescription())->toContain('0 rekening aktif');
});

// ─── NplRatioWidget ──────────────────────────────────────────────────────────

it('can render npl ratio widget', function (): void {
    Livewire::test(NplRatioWidget::class)
        ->assertOk();
});

it('npl ratio widget returns three stats', function (): void {
    $stats = Livewire::test(NplRatioWidget::class)
        ->invade()->getStats();

    expect($stats)->toHaveCount(3);
});

it('npl ratio is zero when no loans exist', function (): void {
    $stats = Livewire::test(NplRatioWidget::class)
        ->invade()->getStats();

    expect($stats[0]->getValue())->toBe('0.00%')
        ->and($stats[0]->getColor())->toBe('success');
});

it('npl ratio calculates correctly with mixed collectibility loans', function (): void {
    // Create current (performing) loan
    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 80_000_000,
        'collectibility' => Collectibility::Current,
        'ckpn_amount' => 0,
    ]);

    // Create NPL loan (Substandard - Kol 3)
    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 20_000_000,
        'collectibility' => Collectibility::Substandard,
        'ckpn_amount' => 3_000_000,
    ]);

    // Total = 100M, NPL = 20M, ratio = 20%
    $stats = Livewire::test(NplRatioWidget::class)
        ->invade()->getStats();

    expect($stats[0]->getValue())->toBe('20.00%')
        ->and($stats[0]->getColor())->toBe('danger')
        ->and($stats[1]->getValue())->toBe('Rp '.number_format(20_000_000, 0, ',', '.'))
        ->and($stats[2]->getValue())->toBe('15.0%'); // 3M / 20M * 100 = 15%
});

it('npl ratio shows healthy status when ratio is under 5 percent', function (): void {
    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 97_000_000,
        'collectibility' => Collectibility::Current,
        'ckpn_amount' => 0,
    ]);

    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 3_000_000,
        'collectibility' => Collectibility::Doubtful,
        'ckpn_amount' => 0,
    ]);

    // Total = 100M, NPL = 3M, ratio = 3%
    $stats = Livewire::test(NplRatioWidget::class)
        ->invade()->getStats();

    expect($stats[0]->getValue())->toBe('3.00%')
        ->and($stats[0]->getColor())->toBe('success')
        ->and($stats[0]->getDescription())->toContain('Sehat');
});

// ─── LoanPortfolioChart ──────────────────────────────────────────────────────

it('can render loan portfolio chart widget', function (): void {
    Livewire::test(LoanPortfolioChart::class)
        ->assertOk();
});

it('loan portfolio chart returns correct data structure', function (): void {
    $data = Livewire::test(LoanPortfolioChart::class)
        ->invade()->getData();

    expect($data)->toHaveKeys(['datasets', 'labels'])
        ->and($data['datasets'])->toBeArray()->toHaveCount(1)
        ->and($data['datasets'][0])->toHaveKeys(['label', 'data', 'backgroundColor'])
        ->and($data['labels'])->toHaveCount(count(Collectibility::cases()));
});

it('loan portfolio chart reflects loan data by collectibility', function (): void {
    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 50_000_000,
        'collectibility' => Collectibility::Current,
    ]);

    LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
        'outstanding_principal' => 10_000_000,
        'collectibility' => Collectibility::Loss,
    ]);

    $data = Livewire::test(LoanPortfolioChart::class)
        ->invade()->getData();

    $amounts = $data['datasets'][0]['data'];

    // Index 0 = Current, Index 4 = Loss
    expect($amounts[0])->toBe(50_000_000.0)
        ->and($amounts[4])->toBe(10_000_000.0)
        ->and($amounts[1])->toBe(0.0) // SpecialMention
        ->and($amounts[2])->toBe(0.0) // Substandard
        ->and($amounts[3])->toBe(0.0); // Doubtful
});

it('loan portfolio chart has five colors matching collectibility cases', function (): void {
    $data = Livewire::test(LoanPortfolioChart::class)
        ->invade()->getData();

    $colors = $data['datasets'][0]['backgroundColor'];

    expect($colors)->toHaveCount(5)
        ->and($colors[0])->toBe('#22c55e')  // Current - green
        ->and($colors[1])->toBe('#eab308')  // SpecialMention - yellow
        ->and($colors[2])->toBe('#f97316')  // Substandard - orange
        ->and($colors[3])->toBe('#ef4444')  // Doubtful - red
        ->and($colors[4])->toBe('#6b7280'); // Loss - gray
});

// ─── PendingApprovalsWidget ──────────────────────────────────────────────────

it('can render pending approvals widget', function (): void {
    Livewire::test(PendingApprovalsWidget::class)
        ->assertOk();
});

it('pending approvals widget returns two stats', function (): void {
    $stats = Livewire::test(PendingApprovalsWidget::class)
        ->invade()->getStats();

    expect($stats)->toHaveCount(2);
});

it('pending approvals widget shows zero when nothing is pending', function (): void {
    $stats = Livewire::test(PendingApprovalsWidget::class)
        ->invade()->getStats();

    expect($stats[0]->getValue())->toBe(0)
        ->and($stats[0]->getColor())->toBe('success')
        ->and($stats[0]->getDescription())->toContain('Tidak ada pending')
        ->and($stats[1]->getValue())->toBe(0)
        ->and($stats[1]->getColor())->toBe('success')
        ->and($stats[1]->getDescription())->toContain('Semua sudah diposting');
});

it('pending approvals widget counts pending loan applications', function (): void {
    LoanApplication::factory()->count(2)->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanApplicationStatus::Submitted,
    ]);

    LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanApplicationStatus::UnderReview,
    ]);

    // Approved should not be counted
    LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanApplicationStatus::Approved,
    ]);

    $stats = Livewire::test(PendingApprovalsWidget::class)
        ->invade()->getStats();

    expect($stats[0]->getValue())->toBe(3)
        ->and($stats[0]->getColor())->toBe('warning')
        ->and($stats[0]->getDescription())->toContain('Menunggu persetujuan');
});

it('pending approvals widget counts draft journal entries', function (): void {
    JournalEntry::factory()->count(4)->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => JournalStatus::Draft,
    ]);

    // Posted should not be counted
    JournalEntry::factory()->posted()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    $stats = Livewire::test(PendingApprovalsWidget::class)
        ->invade()->getStats();

    expect($stats[1]->getValue())->toBe(4)
        ->and($stats[1]->getColor())->toBe('warning')
        ->and($stats[1]->getDescription())->toContain('Menunggu posting');
});
