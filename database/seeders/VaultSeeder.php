<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Seeder;

class VaultSeeder extends Seeder
{
    public function run(): void
    {
        $headOffice = Branch::where('code', '001')->first();
        $admin = User::where('email', 'admin@corebanking.test')->first();

        if (! $headOffice) {
            return;
        }

        Vault::create([
            'code' => 'V001',
            'name' => 'Vault Utama Kantor Pusat',
            'branch_id' => $headOffice->id,
            'balance' => 500000000,
            'minimum_balance' => 100000000,
            'maximum_balance' => 2000000000,
            'is_active' => true,
            'custodian_id' => $admin?->id,
        ]);

        Vault::create([
            'code' => 'V002',
            'name' => 'Vault Teller Kantor Pusat',
            'branch_id' => $headOffice->id,
            'balance' => 100000000,
            'minimum_balance' => 10000000,
            'maximum_balance' => 500000000,
            'is_active' => true,
            'custodian_id' => $admin?->id,
        ]);
    }
}
