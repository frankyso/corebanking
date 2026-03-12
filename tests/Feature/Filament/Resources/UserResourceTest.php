<?php

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
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

it('can render user list page with records', function (): void {
    $users = User::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users)
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('email')
        ->assertCanRenderTableColumn('is_active');
});

it('can search users by name and email', function (): void {
    $users = User::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
    ]);
    $target = $users->first();

    Livewire::test(ListUsers::class)
        ->searchTable($target->email)
        ->assertCanSeeTableRecords($users->where('email', $target->email));
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => null,
            'email' => null,
            'password' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
});

it('can create a user', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'employee_id' => 'EMP099',
            'name' => 'Test User Baru',
            'email' => 'testbaru@example.com',
            'password' => 'password123',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(User::class, [
        'employee_id' => 'EMP099',
        'name' => 'Test User Baru',
        'email' => 'testbaru@example.com',
    ]);
});

it('can load edit page with user data', function (): void {
    $targetUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
        'employee_id' => 'EMP050',
    ]);

    Livewire::test(EditUser::class, ['record' => $targetUser->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('email')
        ->assertFormFieldExists('employee_id')
        ->assertFormFieldExists('branch_id');
});

it('can update a user without changing password', function (): void {
    $targetUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    Livewire::test(EditUser::class, ['record' => $targetUser->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($targetUser->refresh()->name)->toBe('Updated Name');
});
