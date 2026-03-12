<?php

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\PhonesRelationManager;
use App\Models\Branch;
use App\Models\Customer;
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

it('can render customer list page with data', function (): void {
    $customers = Customer::factory()
        ->count(3)
        ->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($customers);
});

it('can filter customers by status', function (): void {
    $active = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
        'status' => CustomerStatus::Active,
    ]);

    $blocked = Customer::factory()->blocked()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(ListCustomers::class)
        ->filterTable('status', CustomerStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$blocked]);
});

it('can filter customers by type', function (): void {
    $individual = Customer::factory()->individual()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    $corporate = Customer::factory()->corporate()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(ListCustomers::class)
        ->filterTable('customer_type', CustomerType::Individual->value)
        ->assertCanSeeTableRecords([$individual])
        ->assertCanNotSeeTableRecords([$corporate]);
});

it('can render customer view page with data', function (): void {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertOk();
});

it('can render addresses relation manager', function (): void {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(AddressesRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])->assertOk();
});

it('can render phones relation manager', function (): void {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(PhonesRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])->assertOk();
});

it('can render documents relation manager', function (): void {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);

    Livewire::test(DocumentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])->assertOk();
});
