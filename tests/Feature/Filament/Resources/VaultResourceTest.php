<?php

use App\Filament\Resources\VaultResource\Pages\CreateVault;
use App\Filament\Resources\VaultResource\Pages\EditVault;
use App\Filament\Resources\VaultResource\Pages\ListVaults;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;
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

it('can render vault list page with records', function (): void {
    $vaults = Vault::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(ListVaults::class)
        ->assertOk()
        ->assertCanSeeTableRecords($vaults)
        ->assertCanRenderTableColumn('code')
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('balance');
});

it('can render vault create page', function (): void {
    Livewire::test(CreateVault::class)
        ->assertOk()
        ->assertFormFieldExists('code')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('branch_id');
});

it('validates required fields on create', function (): void {
    Livewire::test(CreateVault::class)
        ->fillForm([
            'code' => null,
            'name' => null,
            'branch_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
            'branch_id' => 'required',
        ]);
});

it('can create a vault', function (): void {
    Livewire::test(CreateVault::class)
        ->fillForm([
            'code' => 'VT01',
            'name' => 'Vault Utama',
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'balance' => 0,
            'minimum_balance' => 5000000,
            'maximum_balance' => 1000000000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Vault::class, [
        'code' => 'VT01',
        'name' => 'Vault Utama',
        'branch_id' => $this->branch->id,
    ]);
});

it('can load edit page with vault data', function (): void {
    $vault = Vault::factory()->create([
        'branch_id' => $this->branch->id,
        'code' => 'VT99',
        'name' => 'Vault Test Edit',
    ]);

    Livewire::test(EditVault::class, ['record' => $vault->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('code')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('balance');
});
