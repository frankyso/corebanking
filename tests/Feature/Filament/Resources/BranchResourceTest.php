<?php

use App\Filament\Resources\BranchResource\Pages\CreateBranch;
use App\Filament\Resources\BranchResource\Pages\EditBranch;
use App\Filament\Resources\BranchResource\Pages\ListBranches;
use App\Models\Branch;
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

it('can render branch list page with data', function (): void {
    $branches = Branch::factory()->count(3)->create();

    Livewire::test(ListBranches::class)
        ->assertOk()
        ->assertCanSeeTableRecords($branches);
});

it('can search branches by code and name', function (): void {
    $target = Branch::factory()->create(['code' => 'XYZ', 'name' => 'Cabang Khusus']);
    Branch::factory()->create(['code' => 'ABC', 'name' => 'Cabang Lain']);

    Livewire::test(ListBranches::class)
        ->searchTable('XYZ')
        ->assertCanSeeTableRecords([$target]);
});

it('can validate required fields on create', function (): void {
    Livewire::test(CreateBranch::class)
        ->fillForm([
            'code' => '',
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'required', 'name' => 'required']);
});

it('can create a branch', function (): void {
    Livewire::test(CreateBranch::class)
        ->fillForm([
            'code' => 'TST',
            'name' => 'Cabang Test',
            'city' => 'Jakarta',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('branches', [
        'code' => 'TST',
        'name' => 'Cabang Test',
        'city' => 'Jakarta',
    ]);
});

it('can load branch data on edit page', function (): void {
    $branch = Branch::factory()->create([
        'code' => 'EDT',
        'name' => 'Cabang Edit',
    ]);

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->assertOk()
        ->assertFormSet([
            'code' => 'EDT',
            'name' => 'Cabang Edit',
        ]);
});

it('can update a branch', function (): void {
    $branch = Branch::factory()->create([
        'code' => 'OLD',
        'name' => 'Cabang Lama',
    ]);

    Livewire::test(EditBranch::class, ['record' => $branch->getRouteKey()])
        ->fillForm([
            'name' => 'Cabang Baru',
            'city' => 'Surabaya',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'Cabang Baru',
        'city' => 'Surabaya',
    ]);
});

it('can filter branches by active status', function (): void {
    $active = Branch::factory()->create(['is_active' => true]);
    $inactive = Branch::factory()->create(['is_active' => false]);

    Livewire::test(ListBranches::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});
