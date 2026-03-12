<?php

use App\Models\Branch;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\ChartOfAccountPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DepositAccountPolicy;
use App\Policies\DepositProductPolicy;
use App\Policies\HolidayPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\LoanAccountPolicy;
use App\Policies\LoanApplicationPolicy;
use App\Policies\LoanProductPolicy;
use App\Policies\SavingsAccountPolicy;
use App\Policies\SavingsProductPolicy;
use App\Policies\SystemParameterPolicy;
use App\Policies\UserPolicy;
use App\Policies\VaultPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();

    $role = Role::firstOrCreate(['name' => 'Tester', 'guard_name' => 'web']);

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $this->user->assignRole($role);

    $this->dummyModel = new stdClass;
});

/*
|--------------------------------------------------------------------------
| Standard policies (inherit all methods from BankingPolicy)
|--------------------------------------------------------------------------
*/

$standardPolicies = [
    'branch' => [BranchPolicy::class, 'branch'],
    'chart-of-account' => [ChartOfAccountPolicy::class, 'chart-of-account'],
    'customer' => [CustomerPolicy::class, 'customer'],
    'deposit-product' => [DepositProductPolicy::class, 'deposit-product'],
    'holiday' => [HolidayPolicy::class, 'holiday'],
    'loan-product' => [LoanProductPolicy::class, 'loan-product'],
    'savings-product' => [SavingsProductPolicy::class, 'savings-product'],
    'user' => [UserPolicy::class, 'user'],
];

foreach ($standardPolicies as $label => [$policyClass, $module]) {
    describe("$label policy", function () use ($policyClass, $module): void {
        it("grants viewAny when user has {$module}.view permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.view", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.view");

            $policy = new $policyClass;
            expect($policy->viewAny($this->user))->toBeTrue();
        });

        it("denies viewAny when user lacks {$module}.view permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.view", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->viewAny($this->user))->toBeFalse();
        });

        it("grants view when user has {$module}.view permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.view", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.view");

            $policy = new $policyClass;
            expect($policy->view($this->user, $this->dummyModel))->toBeTrue();
        });

        it("denies view when user lacks {$module}.view permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.view", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->view($this->user, $this->dummyModel))->toBeFalse();
        });

        it("grants create when user has {$module}.create permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.create", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.create");

            $policy = new $policyClass;
            expect($policy->create($this->user))->toBeTrue();
        });

        it("denies create when user lacks {$module}.create permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.create", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->create($this->user))->toBeFalse();
        });

        it("grants update when user has {$module}.update permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.update", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.update");

            $policy = new $policyClass;
            expect($policy->update($this->user, $this->dummyModel))->toBeTrue();
        });

        it("denies update when user lacks {$module}.update permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.update", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->update($this->user, $this->dummyModel))->toBeFalse();
        });

        it("grants delete when user has {$module}.delete permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.delete", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.delete");

            $policy = new $policyClass;
            expect($policy->delete($this->user, $this->dummyModel))->toBeTrue();
        });

        it("denies delete when user lacks {$module}.delete permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.delete", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->delete($this->user, $this->dummyModel))->toBeFalse();
        });

        it("grants deleteAny when user has {$module}.delete permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.delete", 'guard_name' => 'web']);
            $this->user->givePermissionTo("{$module}.delete");

            $policy = new $policyClass;
            expect($policy->deleteAny($this->user))->toBeTrue();
        });

        it("denies deleteAny when user lacks {$module}.delete permission", function () use ($policyClass, $module): void {
            Permission::firstOrCreate(['name' => "{$module}.delete", 'guard_name' => 'web']);

            $policy = new $policyClass;
            expect($policy->deleteAny($this->user))->toBeFalse();
        });
    });
}

/*
|--------------------------------------------------------------------------
| Policies with overridden methods that always return false
|--------------------------------------------------------------------------
*/

describe('savings-account policy', function (): void {
    it('grants viewAny with savings-account.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.view');

        expect((new SavingsAccountPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('grants view with savings-account.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.view');

        expect((new SavingsAccountPolicy)->view($this->user, $this->dummyModel))->toBeTrue();
    });

    it('grants create with savings-account.create permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.create');

        expect((new SavingsAccountPolicy)->create($this->user))->toBeTrue();
    });

    it('always denies update regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.update');

        expect((new SavingsAccountPolicy)->update($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.delete');

        expect((new SavingsAccountPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'savings-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('savings-account.delete');

        expect((new SavingsAccountPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('deposit-account policy', function (): void {
    it('grants viewAny with deposit-account.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'deposit-account.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('deposit-account.view');

        expect((new DepositAccountPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('grants create with deposit-account.create permission', function (): void {
        Permission::firstOrCreate(['name' => 'deposit-account.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('deposit-account.create');

        expect((new DepositAccountPolicy)->create($this->user))->toBeTrue();
    });

    it('always denies update regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'deposit-account.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('deposit-account.update');

        expect((new DepositAccountPolicy)->update($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'deposit-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('deposit-account.delete');

        expect((new DepositAccountPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'deposit-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('deposit-account.delete');

        expect((new DepositAccountPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('journal-entry policy', function (): void {
    it('grants viewAny with journal.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'journal.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('journal.view');

        expect((new JournalEntryPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('grants create with journal.create permission', function (): void {
        Permission::firstOrCreate(['name' => 'journal.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('journal.create');

        expect((new JournalEntryPolicy)->create($this->user))->toBeTrue();
    });

    it('always denies update regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'journal.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('journal.update');

        expect((new JournalEntryPolicy)->update($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'journal.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('journal.delete');

        expect((new JournalEntryPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'journal.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('journal.delete');

        expect((new JournalEntryPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('loan-account policy', function (): void {
    it('grants viewAny with loan-account.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-account.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-account.view');

        expect((new LoanAccountPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('always denies create regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-account.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-account.create');

        expect((new LoanAccountPolicy)->create($this->user))->toBeFalse();
    });

    it('always denies update regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-account.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-account.update');

        expect((new LoanAccountPolicy)->update($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-account.delete');

        expect((new LoanAccountPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-account.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-account.delete');

        expect((new LoanAccountPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('loan-application policy', function (): void {
    it('grants viewAny with loan-application.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-application.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-application.view');

        expect((new LoanApplicationPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('grants create with loan-application.create permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-application.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-application.create');

        expect((new LoanApplicationPolicy)->create($this->user))->toBeTrue();
    });

    it('grants update with loan-application.update permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-application.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-application.update');

        expect((new LoanApplicationPolicy)->update($this->user, $this->dummyModel))->toBeTrue();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-application.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-application.delete');

        expect((new LoanApplicationPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'loan-application.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('loan-application.delete');

        expect((new LoanApplicationPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('system-parameter policy', function (): void {
    it('grants viewAny with system-parameter.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'system-parameter.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('system-parameter.view');

        expect((new SystemParameterPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('grants update with system-parameter.update permission', function (): void {
        Permission::firstOrCreate(['name' => 'system-parameter.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('system-parameter.update');

        expect((new SystemParameterPolicy)->update($this->user, $this->dummyModel))->toBeTrue();
    });

    it('always denies create regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'system-parameter.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('system-parameter.create');

        expect((new SystemParameterPolicy)->create($this->user))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'system-parameter.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('system-parameter.delete');

        expect((new SystemParameterPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'system-parameter.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('system-parameter.delete');

        expect((new SystemParameterPolicy)->deleteAny($this->user))->toBeFalse();
    });
});

describe('vault policy', function (): void {
    it('grants viewAny with vault.view permission', function (): void {
        Permission::firstOrCreate(['name' => 'vault.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('vault.view');

        expect((new VaultPolicy)->viewAny($this->user))->toBeTrue();
    });

    it('always denies create regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'vault.create', 'guard_name' => 'web']);
        $this->user->givePermissionTo('vault.create');

        expect((new VaultPolicy)->create($this->user))->toBeFalse();
    });

    it('always denies update regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'vault.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('vault.update');

        expect((new VaultPolicy)->update($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies delete regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'vault.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('vault.delete');

        expect((new VaultPolicy)->delete($this->user, $this->dummyModel))->toBeFalse();
    });

    it('always denies deleteAny regardless of permission', function (): void {
        Permission::firstOrCreate(['name' => 'vault.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('vault.delete');

        expect((new VaultPolicy)->deleteAny($this->user))->toBeFalse();
    });
});
