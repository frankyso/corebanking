<?php

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Filament\Resources\LoanAccountResource\Pages\ListLoanAccounts;
use App\Filament\Resources\LoanAccountResource\Pages\ViewLoanAccount;
use App\Filament\Resources\LoanAccountResource\RelationManagers\CollateralsRelationManager;
use App\Filament\Resources\LoanAccountResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\LoanAccountResource\RelationManagers\SchedulesRelationManager;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
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

it('can render loan account list page with data', function (): void {
    $accounts = LoanAccount::factory()
        ->count(3)
        ->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

    Livewire::test(ListLoanAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($accounts);
});

it('can filter loan accounts by status', function (): void {
    $active = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Active,
    ]);

    $closed = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanStatus::Closed,
    ]);

    Livewire::test(ListLoanAccounts::class)
        ->filterTable('status', LoanStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$closed]);
});

it('can filter loan accounts by collectibility', function (): void {
    $current = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'collectibility' => Collectibility::Current,
    ]);

    $substandard = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'collectibility' => Collectibility::Substandard,
    ]);

    Livewire::test(ListLoanAccounts::class)
        ->filterTable('collectibility', Collectibility::Current->value)
        ->assertCanSeeTableRecords([$current])
        ->assertCanNotSeeTableRecords([$substandard]);
});

it('can render loan account view page with infolist', function (): void {
    $account = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewLoanAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

it('can render schedules relation manager', function (): void {
    $account = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(SchedulesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewLoanAccount::class,
    ])->assertOk();
});

it('can render payments relation manager', function (): void {
    $account = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(PaymentsRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewLoanAccount::class,
    ])->assertOk();
});

it('can render collaterals relation manager', function (): void {
    $account = LoanAccount::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(CollateralsRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewLoanAccount::class,
    ])->assertOk();
});
