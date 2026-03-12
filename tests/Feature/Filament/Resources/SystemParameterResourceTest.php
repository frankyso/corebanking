<?php

use App\Filament\Resources\SystemParameterResource\Pages\CreateSystemParameter;
use App\Filament\Resources\SystemParameterResource\Pages\EditSystemParameter;
use App\Filament\Resources\SystemParameterResource\Pages\ListSystemParameters;
use App\Models\Branch;
use App\Models\SystemParameter;
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

it('can render system parameter list page with records', function (): void {
    $params = SystemParameter::factory()->count(3)->create();

    Livewire::test(ListSystemParameters::class)
        ->assertOk()
        ->assertCanSeeTableRecords($params)
        ->assertCanRenderTableColumn('group')
        ->assertCanRenderTableColumn('key')
        ->assertCanRenderTableColumn('value');
});

it('can search system parameters by key', function (): void {
    $params = SystemParameter::factory()->count(5)->create();
    $target = $params->first();

    Livewire::test(ListSystemParameters::class)
        ->searchTable($target->key)
        ->assertCanSeeTableRecords($params->where('key', $target->key));
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateSystemParameter::class)
        ->fillForm([
            'group' => null,
            'key' => null,
            'value' => null,
            'type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'group' => 'required',
            'key' => 'required',
            'value' => 'required',
            'type' => 'required',
        ]);
});

it('can load edit page with system parameter data', function (): void {
    $param = SystemParameter::factory()->create([
        'group' => 'LOAN',
        'key' => 'max_tenor',
        'value' => '60',
        'type' => 'integer',
    ]);

    Livewire::test(EditSystemParameter::class, ['record' => $param->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('group')
        ->assertFormFieldExists('key')
        ->assertFormFieldExists('value')
        ->assertFormFieldExists('type');
});

it('can update a system parameter', function (): void {
    $param = SystemParameter::factory()->create([
        'group' => 'LOAN',
        'key' => 'max_tenor',
        'value' => '60',
        'type' => 'integer',
    ]);

    Livewire::test(EditSystemParameter::class, ['record' => $param->getRouteKey()])
        ->fillForm([
            'value' => '120',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($param->refresh()->value)->toBe('120');
});
