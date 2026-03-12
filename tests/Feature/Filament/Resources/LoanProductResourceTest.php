<?php

use App\Enums\InterestType;
use App\Enums\LoanType;
use App\Filament\Resources\LoanProductResource\Pages\CreateLoanProduct;
use App\Filament\Resources\LoanProductResource\Pages\EditLoanProduct;
use App\Filament\Resources\LoanProductResource\Pages\ListLoanProducts;
use App\Models\Branch;
use App\Models\LoanProduct;
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

it('can render loan product list page with records', function (): void {
    $products = LoanProduct::factory()->count(3)->create();

    Livewire::test(ListLoanProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($products)
        ->assertCanRenderTableColumn('code')
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('loan_type')
        ->assertCanRenderTableColumn('interest_type')
        ->assertCanRenderTableColumn('interest_rate');
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateLoanProduct::class)
        ->fillForm([
            'code' => null,
            'name' => null,
            'loan_type' => null,
            'interest_type' => null,
            'min_amount' => null,
            'interest_rate' => null,
            'min_tenor_months' => null,
            'max_tenor_months' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
            'loan_type' => 'required',
            'interest_type' => 'required',
            'min_amount' => 'required',
            'interest_rate' => 'required',
            'min_tenor_months' => 'required',
            'max_tenor_months' => 'required',
        ]);
});

it('can create a loan product', function (): void {
    Livewire::test(CreateLoanProduct::class)
        ->fillForm([
            'code' => 'L01',
            'name' => 'Kredit Modal Kerja',
            'loan_type' => LoanType::Kmk->value,
            'interest_type' => InterestType::Annuity->value,
            'min_amount' => 1000000,
            'max_amount' => 500000000,
            'interest_rate' => 12,
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
            'admin_fee_rate' => 1.0,
            'provision_fee_rate' => 0.5,
            'penalty_rate' => 0.5,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(LoanProduct::class, [
        'code' => 'L01',
        'name' => 'Kredit Modal Kerja',
        'loan_type' => LoanType::Kmk->value,
        'interest_type' => InterestType::Annuity->value,
    ]);
});

it('can load edit page with loan product data', function (): void {
    $product = LoanProduct::factory()->create();

    Livewire::test(EditLoanProduct::class, ['record' => $product->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('code')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('loan_type')
        ->assertFormFieldExists('interest_type')
        ->assertFormFieldExists('interest_rate')
        ->assertFormFieldExists('min_tenor_months');
});

it('can update a loan product', function (): void {
    $product = LoanProduct::factory()->create();

    Livewire::test(EditLoanProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Kredit Investasi Updated',
            'interest_rate' => 15.5,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh())
        ->name->toBe('Kredit Investasi Updated')
        ->interest_rate->toBe('15.50000');
});

it('can filter loan products by type', function (): void {
    $kmk = LoanProduct::factory()->create(['loan_type' => LoanType::Kmk]);
    $ki = LoanProduct::factory()->create(['loan_type' => LoanType::Ki]);

    Livewire::test(ListLoanProducts::class)
        ->filterTable('loan_type', LoanType::Kmk->value)
        ->assertCanSeeTableRecords([$kmk])
        ->assertCanNotSeeTableRecords([$ki]);
});
