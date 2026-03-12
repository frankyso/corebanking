<?php

use App\Enums\InterestCalcMethod;
use App\Filament\Resources\SavingsProductResource\Pages\CreateSavingsProduct;
use App\Filament\Resources\SavingsProductResource\Pages\EditSavingsProduct;
use App\Filament\Resources\SavingsProductResource\Pages\ListSavingsProducts;
use App\Models\Branch;
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

it('can render savings product list page with records', function (): void {
    $products = SavingsProduct::factory()->count(3)->create();

    Livewire::test(ListSavingsProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($products)
        ->assertCanRenderTableColumn('code')
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('interest_rate')
        ->assertCanRenderTableColumn('interest_calc_method');
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateSavingsProduct::class)
        ->fillForm([
            'code' => null,
            'name' => null,
            'interest_calc_method' => null,
            'interest_rate' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
            'interest_calc_method' => 'required',
            'interest_rate' => 'required',
        ]);
});

it('can create a savings product', function (): void {
    Livewire::test(CreateSavingsProduct::class)
        ->fillForm([
            'code' => 'T01',
            'name' => 'Tabungan Utama',
            'interest_calc_method' => InterestCalcMethod::DailyBalance->value,
            'interest_rate' => 3.5,
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'admin_fee_monthly' => 5000,
            'closing_fee' => 25000,
            'tax_rate' => 20,
            'tax_threshold' => 7500000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(SavingsProduct::class, [
        'code' => 'T01',
        'name' => 'Tabungan Utama',
        'interest_calc_method' => InterestCalcMethod::DailyBalance->value,
    ]);
});

it('can load edit page with savings product data', function (): void {
    $product = SavingsProduct::factory()->create();

    Livewire::test(EditSavingsProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('code')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('interest_calc_method')
        ->assertFormFieldExists('interest_rate')
        ->assertFormFieldExists('min_opening_balance');
});

it('can update a savings product', function (): void {
    $product = SavingsProduct::factory()->create();

    Livewire::test(EditSavingsProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Tabungan Premium Updated',
            'interest_rate' => 4.5,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh())
        ->name->toBe('Tabungan Premium Updated')
        ->interest_rate->toBe('4.50000');
});

it('enforces unique code on create', function (): void {
    SavingsProduct::factory()->create(['code' => 'DUP']);

    Livewire::test(CreateSavingsProduct::class)
        ->fillForm([
            'code' => 'DUP',
            'name' => 'Duplicate Savings',
            'interest_calc_method' => InterestCalcMethod::DailyBalance->value,
            'interest_rate' => 3.0,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});
