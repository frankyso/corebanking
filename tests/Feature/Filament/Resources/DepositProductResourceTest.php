<?php

use App\Filament\Resources\DepositProductResource\Pages\CreateDepositProduct;
use App\Filament\Resources\DepositProductResource\Pages\EditDepositProduct;
use App\Filament\Resources\DepositProductResource\Pages\ListDepositProducts;
use App\Filament\Resources\DepositProductResource\RelationManagers\RatesRelationManager;
use App\Models\Branch;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
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

it('can render deposit product list page with records', function (): void {
    $products = DepositProduct::factory()->count(3)->create();

    Livewire::test(ListDepositProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($products)
        ->assertCanRenderTableColumn('code')
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('min_amount')
        ->assertCanRenderTableColumn('penalty_rate');
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateDepositProduct::class)
        ->fillForm([
            'code' => null,
            'name' => null,
            'min_amount' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
            'min_amount' => 'required',
        ]);
});

it('can create a deposit product', function (): void {
    Livewire::test(CreateDepositProduct::class)
        ->fillForm([
            'code' => 'D01',
            'name' => 'Deposito Berjangka',
            'currency' => 'IDR',
            'min_amount' => 1000000,
            'max_amount' => 2000000000,
            'penalty_rate' => 0.5,
            'tax_rate' => 20,
            'tax_threshold' => 7500000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(DepositProduct::class, [
        'code' => 'D01',
        'name' => 'Deposito Berjangka',
    ]);
});

it('can load edit page with deposit product data', function (): void {
    $product = DepositProduct::factory()->create();

    Livewire::test(EditDepositProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('code')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('min_amount')
        ->assertFormFieldExists('penalty_rate')
        ->assertFormFieldExists('tax_rate');
});

it('can render rates relation manager on edit page', function (): void {
    $product = DepositProduct::factory()->create();

    DepositProductRate::create([
        'deposit_product_id' => $product->id,
        'tenor_months' => 3,
        'min_amount' => 1000000,
        'max_amount' => 100000000,
        'interest_rate' => 5.5,
        'is_active' => true,
    ]);

    Livewire::test(RatesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditDepositProduct::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($product->rates);
});

it('enforces unique code on create', function (): void {
    DepositProduct::factory()->create(['code' => 'DUP']);

    Livewire::test(CreateDepositProduct::class)
        ->fillForm([
            'code' => 'DUP',
            'name' => 'Duplicate Product',
            'min_amount' => 1000000,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});
