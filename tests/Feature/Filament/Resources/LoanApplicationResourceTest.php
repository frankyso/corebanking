<?php

use App\Enums\LoanApplicationStatus;
use App\Filament\Resources\LoanApplicationResource\Pages\CreateLoanApplication;
use App\Filament\Resources\LoanApplicationResource\Pages\ListLoanApplications;
use App\Filament\Resources\LoanApplicationResource\Pages\ViewLoanApplication;
use App\Filament\Resources\LoanApplicationResource\RelationManagers\CollateralsRelationManager;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
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

    $this->customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'approved_by' => $this->user->id,
    ]);
});

it('can render loan application list page with data', function (): void {
    $applications = LoanApplication::factory()
        ->count(3)
        ->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

    Livewire::test(ListLoanApplications::class)
        ->assertOk()
        ->assertCanSeeTableRecords($applications);
});

it('can filter loan applications by status', function (): void {
    $submitted = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanApplicationStatus::Submitted,
    ]);

    $rejected = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => LoanApplicationStatus::Rejected,
    ]);

    Livewire::test(ListLoanApplications::class)
        ->filterTable('status', LoanApplicationStatus::Submitted->value)
        ->assertCanSeeTableRecords([$submitted])
        ->assertCanNotSeeTableRecords([$rejected]);
});

it('can filter loan applications by product', function (): void {
    $product1 = LoanProduct::factory()->create();
    $product2 = LoanProduct::factory()->create();

    $app1 = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'loan_product_id' => $product1->id,
    ]);

    $app2 = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'loan_product_id' => $product2->id,
    ]);

    Livewire::test(ListLoanApplications::class)
        ->filterTable('loan_product_id', $product1->id)
        ->assertCanSeeTableRecords([$app1])
        ->assertCanNotSeeTableRecords([$app2]);
});

it('can render loan application view page', function (): void {
    $application = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewLoanApplication::class, ['record' => $application->getRouteKey()])
        ->assertOk();
});

it('can render create loan application page', function (): void {
    Livewire::test(CreateLoanApplication::class)
        ->assertOk();
});

it('can validate required fields on create', function (): void {
    Livewire::test(CreateLoanApplication::class)
        ->fillForm([
            'customer_id' => null,
            'loan_product_id' => null,
            'requested_amount' => null,
            'requested_tenor_months' => null,
            'purpose' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'customer_id' => 'required',
            'loan_product_id' => 'required',
            'requested_amount' => 'required',
            'requested_tenor_months' => 'required',
            'purpose' => 'required',
        ]);
});

it('can render collaterals relation manager', function (): void {
    $application = LoanApplication::factory()->create([
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(CollateralsRelationManager::class, [
        'ownerRecord' => $application,
        'pageClass' => ViewLoanApplication::class,
    ])->assertOk();
});
