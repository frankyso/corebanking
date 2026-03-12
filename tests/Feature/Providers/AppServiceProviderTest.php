<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

describe('AppServiceProvider Gate::before', function (): void {
    it('SuperAdmin bypasses all Gate checks and returns true for any permission', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->actingAs($user);

        expect(Gate::allows('any-random-permission'))->toBeTrue()
            ->and(Gate::allows('teller.open-session'))->toBeTrue()
            ->and(Gate::allows('eod.execute'))->toBeTrue()
            ->and(Gate::allows('report.view'))->toBeTrue()
            ->and(Gate::allows('nonexistent.permission'))->toBeTrue();
    });

    it('regular user does NOT get auto-granted permissions', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        expect(Gate::allows('any-random-permission'))->toBeFalse()
            ->and(Gate::allows('teller.open-session'))->toBeFalse()
            ->and(Gate::allows('eod.execute'))->toBeFalse();
    });

    it('Gate::before returns null for non-SuperAdmin to fall through to normal permission check', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $regularRole = Role::firstOrCreate(['name' => 'Teller', 'guard_name' => 'web']);
        $user->assignRole($regularRole);

        $this->actingAs($user);

        // Without any explicit permission, the Gate should deny
        expect(Gate::allows('some.permission'))->toBeFalse();
    });

    it('user with specific permission gets access only to that permission', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'Teller', 'guard_name' => 'web']);
        $user->assignRole($role);
        $role->givePermissionTo(
            Permission::firstOrCreate(['name' => 'teller.open-session', 'guard_name' => 'web'])
        );

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($user);

        expect(Gate::allows('teller.open-session'))->toBeTrue()
            ->and(Gate::allows('eod.execute'))->toBeFalse();
    });
});
