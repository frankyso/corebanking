<?php

use App\Filament\Resources\BranchResource;
use App\Filament\Resources\BranchResource\Pages\CreateBranch;
use App\Filament\Resources\BranchResource\Pages\EditBranch;
use App\Filament\Resources\BranchResource\Pages\ListBranches;
use App\Filament\Resources\ChartOfAccountResource;
use App\Filament\Resources\ChartOfAccountResource\Pages\CreateChartOfAccount;
use App\Filament\Resources\ChartOfAccountResource\Pages\EditChartOfAccount;
use App\Filament\Resources\ChartOfAccountResource\Pages\ListChartOfAccounts;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\DepositAccountResource;
use App\Filament\Resources\DepositAccountResource\Pages\CreateDepositAccount;
use App\Filament\Resources\DepositAccountResource\Pages\ListDepositAccounts;
use App\Filament\Resources\DepositAccountResource\Pages\ViewDepositAccount;
use App\Filament\Resources\DepositProductResource;
use App\Filament\Resources\DepositProductResource\Pages\CreateDepositProduct;
use App\Filament\Resources\DepositProductResource\Pages\EditDepositProduct;
use App\Filament\Resources\DepositProductResource\Pages\ListDepositProducts;
use App\Filament\Resources\HolidayResource;
use App\Filament\Resources\HolidayResource\Pages\CreateHoliday;
use App\Filament\Resources\HolidayResource\Pages\EditHoliday;
use App\Filament\Resources\HolidayResource\Pages\ListHolidays;
use App\Filament\Resources\JournalEntryResource;
use App\Filament\Resources\JournalEntryResource\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntryResource\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntryResource\Pages\ViewJournalEntry;
use App\Filament\Resources\LoanAccountResource;
use App\Filament\Resources\LoanAccountResource\Pages\ListLoanAccounts;
use App\Filament\Resources\LoanAccountResource\Pages\ViewLoanAccount;
use App\Filament\Resources\LoanApplicationResource;
use App\Filament\Resources\LoanApplicationResource\Pages\CreateLoanApplication;
use App\Filament\Resources\LoanApplicationResource\Pages\ListLoanApplications;
use App\Filament\Resources\LoanApplicationResource\Pages\ViewLoanApplication;
use App\Filament\Resources\LoanProductResource;
use App\Filament\Resources\LoanProductResource\Pages\CreateLoanProduct;
use App\Filament\Resources\LoanProductResource\Pages\EditLoanProduct;
use App\Filament\Resources\LoanProductResource\Pages\ListLoanProducts;
use App\Filament\Resources\SavingsAccountResource;
use App\Filament\Resources\SavingsAccountResource\Pages\CreateSavingsAccount;
use App\Filament\Resources\SavingsAccountResource\Pages\ListSavingsAccounts;
use App\Filament\Resources\SavingsAccountResource\Pages\ViewSavingsAccount;
use App\Filament\Resources\SavingsProductResource;
use App\Filament\Resources\SavingsProductResource\Pages\CreateSavingsProduct;
use App\Filament\Resources\SavingsProductResource\Pages\EditSavingsProduct;
use App\Filament\Resources\SavingsProductResource\Pages\ListSavingsProducts;
use App\Filament\Resources\SystemParameterResource;
use App\Filament\Resources\SystemParameterResource\Pages\CreateSystemParameter;
use App\Filament\Resources\SystemParameterResource\Pages\EditSystemParameter;
use App\Filament\Resources\SystemParameterResource\Pages\ListSystemParameters;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\VaultResource;
use App\Filament\Resources\VaultResource\Pages\CreateVault;
use App\Filament\Resources\VaultResource\Pages\EditVault;
use App\Filament\Resources\VaultResource\Pages\ListVaults;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\Holiday;
use App\Models\JournalEntry;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SystemParameter;
use App\Models\User;
use App\Models\Vault;
use Livewire\Livewire;

beforeEach(function () {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $this->actingAs($this->user);
});

// ─── BranchResource ─────────────────────────────────────────────────────────

it('can render branch list page', function () {
    Livewire::test(ListBranches::class)
        ->assertOk();
});

it('can render branch create page', function () {
    Livewire::test(CreateBranch::class)
        ->assertOk();
});

it('can render branch edit page', function () {
    $branch = Branch::factory()->create();

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->assertOk();
});

// ─── HolidayResource ────────────────────────────────────────────────────────

it('can render holiday list page', function () {
    Livewire::test(ListHolidays::class)
        ->assertOk();
});

it('can render holiday create page', function () {
    Livewire::test(CreateHoliday::class)
        ->assertOk();
});

it('can render holiday edit page', function () {
    $holiday = Holiday::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->getRouteKey()])
        ->assertOk();
});

// ─── SystemParameterResource ────────────────────────────────────────────────

it('can render system parameter list page', function () {
    Livewire::test(ListSystemParameters::class)
        ->assertOk();
});

it('can render system parameter create page', function () {
    Livewire::test(CreateSystemParameter::class)
        ->assertOk();
});

it('can render system parameter edit page', function () {
    $param = SystemParameter::factory()->create();

    Livewire::test(EditSystemParameter::class, ['record' => $param->getRouteKey()])
        ->assertOk();
});

// ─── UserResource ───────────────────────────────────────────────────────────

it('can render user list page', function () {
    Livewire::test(ListUsers::class)
        ->assertOk();
});

it('can render user create page', function () {
    Livewire::test(CreateUser::class)
        ->assertOk();
});

it('can render user edit page', function () {
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertOk();
});

// ─── ChartOfAccountResource ────────────────────────────────────────────────

it('can render chart of account list page', function () {
    Livewire::test(ListChartOfAccounts::class)
        ->assertOk();
});

it('can render chart of account create page', function () {
    Livewire::test(CreateChartOfAccount::class)
        ->assertOk();
});

it('can render chart of account edit page', function () {
    $coa = ChartOfAccount::factory()->create();

    Livewire::test(EditChartOfAccount::class, ['record' => $coa->getRouteKey()])
        ->assertOk();
});

// ─── CustomerResource ──────────────────────────────────────────────────────

it('can render customer list page', function () {
    Livewire::test(ListCustomers::class)
        ->assertOk();
});

it('can render customer create page', function () {
    Livewire::test(CreateCustomer::class)
        ->assertOk();
});

it('can render customer view page', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertOk();
});

// ─── SavingsProductResource ────────────────────────────────────────────────

it('can render savings product list page', function () {
    Livewire::test(ListSavingsProducts::class)
        ->assertOk();
});

it('can render savings product create page', function () {
    Livewire::test(CreateSavingsProduct::class)
        ->assertOk();
});

it('can render savings product edit page', function () {
    $product = SavingsProduct::factory()->create();

    Livewire::test(EditSavingsProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk();
});

// ─── SavingsAccountResource ────────────────────────────────────────────────

it('can render savings account list page', function () {
    Livewire::test(ListSavingsAccounts::class)
        ->assertOk();
});

it('can render savings account create page', function () {
    Livewire::test(CreateSavingsAccount::class)
        ->assertOk();
});

it('can render savings account view page', function () {
    $account = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

// ─── DepositProductResource ────────────────────────────────────────────────

it('can render deposit product list page', function () {
    Livewire::test(ListDepositProducts::class)
        ->assertOk();
});

it('can render deposit product create page', function () {
    Livewire::test(CreateDepositProduct::class)
        ->assertOk();
});

it('can render deposit product edit page', function () {
    $product = DepositProduct::factory()->create();

    Livewire::test(EditDepositProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk();
});

// ─── DepositAccountResource ────────────────────────────────────────────────

it('can render deposit account list page', function () {
    Livewire::test(ListDepositAccounts::class)
        ->assertOk();
});

it('can render deposit account create page', function () {
    Livewire::test(CreateDepositAccount::class)
        ->assertOk();
});

it('can render deposit account view page', function () {
    $account = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewDepositAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

// ─── JournalEntryResource ──────────────────────────────────────────────────

it('can render journal entry list page', function () {
    Livewire::test(ListJournalEntries::class)
        ->assertOk();
});

it('can render journal entry create page', function () {
    Livewire::test(CreateJournalEntry::class)
        ->assertOk();
});

it('can render journal entry view page', function () {
    $journal = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewJournalEntry::class, ['record' => $journal->getRouteKey()])
        ->assertOk();
});

// ─── LoanProductResource ───────────────────────────────────────────────────

it('can render loan product list page', function () {
    Livewire::test(ListLoanProducts::class)
        ->assertOk();
});

it('can render loan product create page', function () {
    Livewire::test(CreateLoanProduct::class)
        ->assertOk();
});

it('can render loan product edit page', function () {
    $product = LoanProduct::factory()->create();

    Livewire::test(EditLoanProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk();
});

// ─── LoanApplicationResource ───────────────────────────────────────────────

it('can render loan application list page', function () {
    Livewire::test(ListLoanApplications::class)
        ->assertOk();
});

it('can render loan application create page', function () {
    Livewire::test(CreateLoanApplication::class)
        ->assertOk();
});

it('can render loan application view page', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    $application = LoanApplication::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewLoanApplication::class, ['record' => $application->getRouteKey()])
        ->assertOk();
});

// ─── LoanAccountResource ───────────────────────────────────────────────────

it('can render loan account list page', function () {
    Livewire::test(ListLoanAccounts::class)
        ->assertOk();
});

it('can render loan account view page', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    $account = LoanAccount::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewLoanAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

// ─── VaultResource ─────────────────────────────────────────────────────────

it('can render vault list page', function () {
    Livewire::test(ListVaults::class)
        ->assertOk();
});

it('can render vault create page', function () {
    Livewire::test(CreateVault::class)
        ->assertOk();
});

it('can render vault edit page', function () {
    $vault = Vault::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(EditVault::class, ['record' => $vault->getRouteKey()])
        ->assertOk();
});
