<?php

use App\Enums\DepositStatus;
use App\Filament\Resources\DepositAccountResource\Pages\ListDepositAccounts;
use App\Filament\Resources\DepositAccountResource\Pages\ViewDepositAccount;
use App\Filament\Resources\DepositAccountResource\RelationManagers\TransactionsRelationManager;
use App\Models\Branch;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
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

it('can render deposit account list page with data', function (): void {
    $accounts = DepositAccount::factory()
        ->count(3)
        ->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

    Livewire::test(ListDepositAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($accounts);
});

it('can filter deposit accounts by status', function (): void {
    $active = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => DepositStatus::Active,
    ]);

    $matured = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => DepositStatus::Matured,
    ]);

    Livewire::test(ListDepositAccounts::class)
        ->filterTable('status', DepositStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$matured]);
});

it('can filter deposit accounts by product', function (): void {
    $product1 = DepositProduct::factory()->create();
    $product2 = DepositProduct::factory()->create();

    $account1 = DepositAccount::factory()->create([
        'deposit_product_id' => $product1->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    $account2 = DepositAccount::factory()->create([
        'deposit_product_id' => $product2->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListDepositAccounts::class)
        ->filterTable('deposit_product_id', $product1->id)
        ->assertCanSeeTableRecords([$account1])
        ->assertCanNotSeeTableRecords([$account2]);
});

it('can filter deposit accounts by branch', function (): void {
    $otherBranch = Branch::factory()->create();

    $ours = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    $theirs = DepositAccount::factory()->create([
        'branch_id' => $otherBranch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListDepositAccounts::class)
        ->filterTable('branch_id', $this->branch->id)
        ->assertCanSeeTableRecords([$ours])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('can render deposit account view page', function (): void {
    $account = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewDepositAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

it('can render transactions relation manager', function (): void {
    $account = DepositAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewDepositAccount::class,
    ])->assertOk();
});
