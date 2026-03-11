<?php

use App\Filament\Pages\BalanceSheetPage;
use App\Filament\Pages\EodProcessPage;
use App\Filament\Pages\IncomeStatementPage;
use App\Filament\Pages\LoanPortfolioReport;
use App\Filament\Pages\TellerDashboard;
use App\Filament\Pages\TrialBalancePage;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
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

// ─── TrialBalancePage ──────────────────────────────────────────────────────

it('can render trial balance page', function (): void {
    Livewire::test(TrialBalancePage::class)
        ->assertOk();
});

it('trial balance page initializes with current year and month', function (): void {
    Livewire::test(TrialBalancePage::class)
        ->assertSet('year', (int) now()->year)
        ->assertSet('month', (int) now()->month);
});

// ─── IncomeStatementPage ───────────────────────────────────────────────────

it('can render income statement page', function (): void {
    Livewire::test(IncomeStatementPage::class)
        ->assertOk();
});

it('income statement page initializes with current month date range', function (): void {
    Livewire::test(IncomeStatementPage::class)
        ->assertSet('startDate', now()->startOfMonth()->format('Y-m-d'))
        ->assertSet('endDate', now()->format('Y-m-d'));
});

// ─── BalanceSheetPage ──────────────────────────────────────────────────────

it('can render balance sheet page', function (): void {
    Livewire::test(BalanceSheetPage::class)
        ->assertOk();
});

it('balance sheet page initializes with today as report date', function (): void {
    Livewire::test(BalanceSheetPage::class)
        ->assertSet('reportDate', now()->format('Y-m-d'));
});

// ─── TellerDashboard ──────────────────────────────────────────────────────

it('can render teller dashboard page', function (): void {
    Livewire::test(TellerDashboard::class)
        ->assertOk();
});

// ─── EodProcessPage ───────────────────────────────────────────────────────

it('can render eod process page', function (): void {
    Livewire::test(EodProcessPage::class)
        ->assertOk();
});

it('eod process page initializes with today as process date', function (): void {
    Livewire::test(EodProcessPage::class)
        ->assertSet('processDate', now()->toDateString());
});

// ─── LoanPortfolioReport ──────────────────────────────────────────────────

it('can render loan portfolio report page', function (): void {
    Livewire::test(LoanPortfolioReport::class)
        ->assertOk();
});

it('loan portfolio report summary returns correct data structure', function (): void {
    $component = Livewire::test(LoanPortfolioReport::class);

    $summary = $component->instance()->summary;

    expect($summary)->toBeArray()
        ->and($summary)->toHaveKeys([
            'total_accounts',
            'total_outstanding',
            'total_plafon',
            'total_ckpn',
            'npl_count',
            'npl_amount',
        ]);
});

it('loan portfolio report portfolioByCollectibility returns a collection', function (): void {
    $component = Livewire::test(LoanPortfolioReport::class);

    $portfolio = $component->instance()->portfolioByCollectibility;

    expect($portfolio)->toBeInstanceOf(Collection::class);
});

it('loan portfolio report portfolioByProduct returns a collection', function (): void {
    $component = Livewire::test(LoanPortfolioReport::class);

    $portfolio = $component->instance()->portfolioByProduct;

    expect($portfolio)->toBeInstanceOf(Collection::class);
});
