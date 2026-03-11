<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'branch' => ['view', 'create', 'update', 'delete'],
            'user' => ['view', 'create', 'update', 'delete', 'assign-role'],
            'customer' => ['view', 'create', 'update', 'delete', 'approve', 'block'],
            'savings-product' => ['view', 'create', 'update', 'delete'],
            'savings-account' => ['view', 'create', 'deposit', 'withdraw', 'close', 'freeze'],
            'deposit-product' => ['view', 'create', 'update', 'delete'],
            'deposit-account' => ['view', 'create', 'close', 'rollover'],
            'loan-product' => ['view', 'create', 'update', 'delete'],
            'loan-application' => ['view', 'create', 'update', 'approve', 'reject', 'disburse'],
            'loan-account' => ['view', 'payment', 'restructure', 'write-off'],
            'journal' => ['view', 'create', 'approve', 'reverse'],
            'chart-of-account' => ['view', 'create', 'update', 'delete'],
            'teller' => ['open-session', 'close-session', 'deposit', 'withdraw', 'authorize'],
            'vault' => ['view', 'request-cash', 'return-cash'],
            'report' => ['view', 'export'],
            'system-parameter' => ['view', 'update'],
            'holiday' => ['view', 'create', 'update', 'delete'],
            'eod' => ['view', 'execute'],
            'audit' => ['view'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$module}.{$action}");
            }
        }

        $superAdmin = Role::findOrCreate('SuperAdmin');
        $superAdmin->givePermissionTo(Permission::all());

        $branchManager = Role::findOrCreate('BranchManager');
        $branchManager->givePermissionTo(
            Permission::where('name', 'like', 'customer.%')
                ->orWhere('name', 'like', 'savings-account.%')
                ->orWhere('name', 'like', 'deposit-account.%')
                ->orWhere('name', 'like', 'loan-application.%')
                ->orWhere('name', 'like', 'report.%')
                ->orWhere('name', 'teller.authorize')
                ->get()
        );

        $customerService = Role::findOrCreate('CustomerService');
        $customerService->givePermissionTo([
            'customer.view', 'customer.create', 'customer.update',
            'savings-product.view', 'savings-account.view', 'savings-account.create',
            'deposit-product.view', 'deposit-account.view', 'deposit-account.create',
        ]);

        $teller = Role::findOrCreate('Teller');
        $teller->givePermissionTo([
            'customer.view',
            'savings-account.view', 'savings-account.deposit', 'savings-account.withdraw',
            'teller.open-session', 'teller.close-session', 'teller.deposit', 'teller.withdraw',
        ]);

        $loanOfficer = Role::findOrCreate('LoanOfficer');
        $loanOfficer->givePermissionTo([
            'customer.view',
            'loan-product.view',
            'loan-application.view', 'loan-application.create', 'loan-application.update',
            'loan-account.view', 'loan-account.payment',
        ]);

        $accounting = Role::findOrCreate('Accounting');
        $accounting->givePermissionTo([
            'chart-of-account.view', 'chart-of-account.create', 'chart-of-account.update',
            'journal.view', 'journal.create', 'journal.approve', 'journal.reverse',
            'report.view', 'report.export',
            'eod.view', 'eod.execute',
        ]);

        $auditor = Role::findOrCreate('Auditor');
        $auditor->givePermissionTo([
            'audit.view', 'report.view', 'report.export',
            'journal.view', 'chart-of-account.view',
            'customer.view', 'savings-account.view',
            'deposit-account.view', 'loan-account.view',
        ]);

        $compliance = Role::findOrCreate('Compliance');
        $compliance->givePermissionTo([
            'customer.view', 'audit.view',
            'report.view', 'report.export',
        ]);
    }
}
