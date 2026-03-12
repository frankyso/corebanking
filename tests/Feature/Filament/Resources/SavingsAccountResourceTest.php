<?php

use App\Enums\SavingsAccountStatus;
use App\Filament\Resources\SavingsAccountResource\Pages\ListSavingsAccounts;
use App\Filament\Resources\SavingsAccountResource\Pages\ViewSavingsAccount;
use App\Filament\Resources\SavingsAccountResource\RelationManagers\TransactionsRelationManager;
use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
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

it('can render savings account list page with data', function (): void {
    $accounts = SavingsAccount::factory()
        ->count(3)
        ->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

    Livewire::test(ListSavingsAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($accounts);
});

it('can filter savings accounts by branch', function (): void {
    $otherBranch = Branch::factory()->create();

    $ours = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    $theirs = SavingsAccount::factory()->create([
        'branch_id' => $otherBranch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListSavingsAccounts::class)
        ->filterTable('branch_id', $this->branch->id)
        ->assertCanSeeTableRecords([$ours])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('can filter savings accounts by status', function (): void {
    $active = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => SavingsAccountStatus::Active,
    ]);

    $closed = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => SavingsAccountStatus::Closed,
    ]);

    Livewire::test(ListSavingsAccounts::class)
        ->filterTable('status', SavingsAccountStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$closed]);
});

it('can filter savings accounts by product', function (): void {
    $product1 = SavingsProduct::factory()->create();
    $product2 = SavingsProduct::factory()->create();

    $account1 = SavingsAccount::factory()->create([
        'savings_product_id' => $product1->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    $account2 = SavingsAccount::factory()->create([
        'savings_product_id' => $product2->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListSavingsAccounts::class)
        ->filterTable('savings_product_id', $product1->id)
        ->assertCanSeeTableRecords([$account1])
        ->assertCanNotSeeTableRecords([$account2]);
});

it('can render savings account view page', function (): void {
    $account = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
        ->assertOk();
});

it('can render transactions relation manager', function (): void {
    $account = SavingsAccount::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewSavingsAccount::class,
    ])->assertOk();
});
