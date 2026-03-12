<?php

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use App\Filament\Resources\ChartOfAccountResource\Pages\CreateChartOfAccount;
use App\Filament\Resources\ChartOfAccountResource\Pages\EditChartOfAccount;
use App\Filament\Resources\ChartOfAccountResource\Pages\ListChartOfAccounts;
use App\Models\Branch;
use App\Models\ChartOfAccount;
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

it('can render chart of account list page with records', function (): void {
    $accounts = ChartOfAccount::factory()->count(3)->create();

    Livewire::test(ListChartOfAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($accounts)
        ->assertCanRenderTableColumn('account_code')
        ->assertCanRenderTableColumn('account_group')
        ->assertCanRenderTableColumn('normal_balance');
});

it('can search chart of accounts by code and name', function (): void {
    $accounts = ChartOfAccount::factory()->count(5)->create();
    $target = $accounts->first();

    Livewire::test(ListChartOfAccounts::class)
        ->searchTable($target->account_code)
        ->assertCanSeeTableRecords($accounts->where('account_code', $target->account_code));
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateChartOfAccount::class)
        ->fillForm([
            'account_code' => null,
            'account_name' => null,
            'account_group' => null,
            'level' => null,
            'normal_balance' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'account_code' => 'required',
            'account_name' => 'required',
            'account_group' => 'required',
            'level' => 'required',
            'normal_balance' => 'required',
        ]);
});

it('can create a chart of account', function (): void {
    $parent = ChartOfAccount::factory()->header()->asset()->create();

    Livewire::test(CreateChartOfAccount::class)
        ->fillForm([
            'account_code' => '1.01.01.001',
            'account_name' => 'Kas Besar',
            'account_group' => AccountGroup::Asset->value,
            'parent_id' => $parent->id,
            'level' => 3,
            'normal_balance' => NormalBalance::Debit->value,
            'is_header' => false,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(ChartOfAccount::class, [
        'account_code' => '1.01.01.001',
        'account_name' => 'Kas Besar',
        'account_group' => AccountGroup::Asset->value,
        'parent_id' => $parent->id,
    ]);
});

it('can load data on edit page', function (): void {
    $account = ChartOfAccount::factory()->asset()->create([
        'account_name' => 'Test Account Edit',
    ]);

    Livewire::test(EditChartOfAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('account_code')
        ->assertFormFieldExists('account_name')
        ->assertFormFieldExists('account_group');
});

it('can display parent-child tree structure in list', function (): void {
    $parent = ChartOfAccount::factory()->header()->asset()->create();
    $child = ChartOfAccount::factory()->asset()->childOf($parent)->create();

    Livewire::test(ListChartOfAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$parent, $child]);
});
