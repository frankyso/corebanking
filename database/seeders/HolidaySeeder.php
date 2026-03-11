<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi', 'type' => 'national'],
            ['date' => '2026-01-29', 'name' => 'Tahun Baru Imlek', 'type' => 'national'],
            ['date' => '2026-03-20', 'name' => 'Isra Mikraj Nabi Muhammad SAW', 'type' => 'national'],
            ['date' => '2026-03-22', 'name' => 'Hari Raya Nyepi', 'type' => 'national'],
            ['date' => '2026-04-03', 'name' => 'Wafat Isa Almasih', 'type' => 'national'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'type' => 'national'],
            ['date' => '2026-05-16', 'name' => 'Hari Raya Waisak', 'type' => 'national'],
            ['date' => '2026-05-25', 'name' => 'Kenaikan Isa Almasih', 'type' => 'national'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'type' => 'national'],
            ['date' => '2026-06-17', 'name' => 'Idul Adha', 'type' => 'national'],
            ['date' => '2026-07-07', 'name' => 'Tahun Baru Islam', 'type' => 'national'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI', 'type' => 'national'],
            ['date' => '2026-09-16', 'name' => 'Maulid Nabi Muhammad SAW', 'type' => 'national'],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal', 'type' => 'national'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}
