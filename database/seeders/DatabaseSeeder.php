<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            SystemParameterSeeder::class,
            HolidaySeeder::class,
            ChartOfAccountSeeder::class,
            CustomerSeeder::class,
            SavingsProductSeeder::class,
            SavingsAccountSeeder::class,
            DepositProductSeeder::class,
            DepositAccountSeeder::class,
            LoanProductSeeder::class,
            LoanAccountSeeder::class,
            VaultSeeder::class,
        ]);
    }
}
