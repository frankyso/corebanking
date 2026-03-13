<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManageApiClient extends Command
{
    /** @var string */
    protected $signature = 'api-client {action : create|list|revoke} {--name= : Client name (for create)} {--rate-limit=60 : Requests per minute} {--ips= : Comma-separated allowed IPs}';

    /** @var string */
    protected $description = 'Manage Open API clients (create, list, revoke)';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'create' => $this->createClient(),
            'list' => $this->listClients(),
            'revoke' => $this->revokeClient(),
            default => $this->invalidAction(),
        };
    }

    private function createClient(): int
    {
        $name = $this->option('name');

        if (! $name) {
            $this->error('The --name option is required for create action.');

            return self::FAILURE;
        }

        $clientId = Str::slug($name).'-'.Str::random(6);
        $secretKey = Str::random(64);
        $rateLimit = (int) $this->option('rate-limit');
        $ips = $this->option('ips') ? explode(',', (string) $this->option('ips')) : null;

        ApiClient::create([
            'name' => $name,
            'client_id' => $clientId,
            'secret_key' => $secretKey,
            'is_active' => true,
            'rate_limit' => $rateLimit,
            'allowed_ips' => $ips,
        ]);

        $this->info('API Client created successfully!');
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Client ID', $clientId],
                ['Secret Key', $secretKey],
                ['Rate Limit', "{$rateLimit} req/min"],
                ['Allowed IPs', $ips ? implode(', ', $ips) : 'All'],
            ],
        );
        $this->newLine();
        $this->warn('Save this secret key! It cannot be displayed again.');

        return self::SUCCESS;
    }

    private function listClients(): int
    {
        $clients = ApiClient::all();

        if ($clients->isEmpty()) {
            $this->info('No API clients found.');

            return self::SUCCESS;
        }

        /** @var array<int, array<int, mixed>> $rows */
        $rows = $clients->map(function (ApiClient $client): array {
            /** @var Carbon|null $lastUsedAt */
            $lastUsedAt = $client->last_used_at;

            return [
                $client->id,
                $client->name,
                $client->client_id,
                $client->is_active ? 'Yes' : 'No',
                $client->rate_limit,
                $lastUsedAt?->diffForHumans() ?? 'Never',
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Client ID', 'Active', 'Rate Limit', 'Last Used'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function revokeClient(): int
    {
        /** @var string $clientId */
        $clientId = $this->ask('Enter the Client ID to revoke');

        $client = ApiClient::where('client_id', $clientId)->first();

        if (! $client) {
            $this->error("API Client with ID '{$clientId}' not found.");

            return self::FAILURE;
        }

        $client->update(['is_active' => false]);

        $this->info("API Client '{$client->name}' ({$clientId}) has been revoked.");

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Invalid action. Use: create, list, or revoke.');

        return self::FAILURE;
    }
}
