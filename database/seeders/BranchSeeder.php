<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'code' => '001',
            'name' => 'Kantor Pusat',
            'address' => 'Jl. Utama No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10110',
            'phone' => '021-5550001',
            'is_head_office' => true,
            'is_active' => true,
        ]);
    }
}
