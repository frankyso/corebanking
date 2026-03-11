<?php

use App\Filament\Pages\BalanceSheetPage;
use App\Filament\Pages\EodProcessPage;
use App\Filament\Pages\TellerDashboard;
use App\Filament\Pages\TrialBalancePage;
use App\Filament\Resources\BranchResource\Pages\ListBranches;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\LoanApplicationResource\Pages\ListLoanApplications;
use App\Filament\Resources\LoanApplicationResource\Pages\ViewLoanApplication;
use App\Filament\Widgets\BankOverviewWidget;
use App\Filament\Widgets\LoanPortfolioChart;
use App\Filament\Widgets\NplRatioWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->otherBranch = Branch::factory()->create();
});

// ─── Role-based Menu Visibility ──────────────────────────────────────────

it('denies access to teller dashboard without teller permission', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'LoanOfficer', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'loan-application.view', 'guard_name' => 'web']);
    $role->givePermissionTo('loan-application.view');
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(TellerDashboard::class)
        ->assertForbidden();
});

it('allows teller to access teller dashboard', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'Teller', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'teller.open-session', 'guard_name' => 'web']);
    $role->givePermissionTo('teller.open-session');
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(TellerDashboard::class)
        ->assertOk();
});

it('denies access to eod page without eod permission', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'Teller', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'teller.open-session', 'guard_name' => 'web']);
    $role->givePermissionTo('teller.open-session');
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(EodProcessPage::class)
        ->assertForbidden();
});

it('denies access to report pages without report permission', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'Teller', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'teller.open-session', 'guard_name' => 'web']);
    $role->givePermissionTo('teller.open-session');
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(TrialBalancePage::class)->assertForbidden();
    Livewire::test(BalanceSheetPage::class)->assertForbidden();
});

it('denies access to resource list without view permission', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'LimitedRole', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(ListBranches::class)->assertForbidden();
});

it('allows superadmin to access all pages', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(TellerDashboard::class)->assertOk();
    Livewire::test(EodProcessPage::class)->assertOk();
    Livewire::test(TrialBalancePage::class)->assertOk();
    Livewire::test(BalanceSheetPage::class)->assertOk();
    Livewire::test(ListBranches::class)->assertOk();
    Livewire::test(ListCustomers::class)->assertOk();
    Livewire::test(ListLoanApplications::class)->assertOk();
});

// ─── Branch-scoped Data Filtering ────────────────────────────────────────

it('filters customers by branch for non-admin users', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'CustomerService', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'customer.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'customer.create', 'guard_name' => 'web']);
    $role->givePermissionTo(['customer.view', 'customer.create']);
    $user->assignRole($role);

    $ownCustomer = Customer::factory()->create(['branch_id' => $this->branch->id]);
    $otherCustomer = Customer::factory()->create(['branch_id' => $this->otherBranch->id]);

    $this->actingAs($user);

    $component = Livewire::test(ListCustomers::class);
    $component->assertOk();
    $component->assertSee($ownCustomer->cif_number);
    $component->assertDontSee($otherCustomer->cif_number);
});

// ─── Dashboard Widgets ──────────────────────────────────────────────────

it('can render bank overview widget', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(BankOverviewWidget::class)
        ->assertOk();
});

it('can render npl ratio widget', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(NplRatioWidget::class)
        ->assertOk();
});

it('can render loan portfolio chart widget', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(LoanPortfolioChart::class)
        ->assertOk();
});

it('can render pending approvals widget', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    Livewire::test(PendingApprovalsWidget::class)
        ->assertOk();
});

// ─── View Pages with Infolist ────────────────────────────────────────────

it('can render loan application view page with infolist', function (): void {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
    $application = LoanApplication::factory()->create([
        'branch_id' => $this->branch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
    ]);

    Livewire::test(ViewLoanApplication::class, ['record' => $application->id])
        ->assertOk();
});
