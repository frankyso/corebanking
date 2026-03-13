<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rawSecret = Str::random(64);

        ApiClient::create([
            'name' => 'Development Client',
            'client_id' => 'dev-client-001',
            'secret_key' => $rawSecret,
            'is_active' => true,
            'rate_limit' => 120,
        ]);

        $this->command->info('Dev API Client created:');
        $this->command->info('  Client ID:  dev-client-001');
        $this->command->info("  Secret Key: {$rawSecret}");
        $this->command->warn('  Save this secret key! It cannot be displayed again.');
    }
}
