<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $headOffice = Branch::where('code', '001')->first();

        $admin = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Administrator',
            'email' => 'admin@corebanking.test',
            'password' => bcrypt('password'),
            'branch_id' => $headOffice?->id,
            'is_active' => true,
        ]);

        $admin->assignRole('SuperAdmin');
    }
}
