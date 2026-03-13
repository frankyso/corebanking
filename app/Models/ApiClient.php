<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\ApiClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    /** @use HasFactory<ApiClientFactory> */
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'name',
        'client_id',
        'secret_key',
        'is_active',
        'rate_limit',
        'allowed_ips',
        'permissions',
        'last_used_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_ips' => 'array',
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'secret_key' => 'encrypted',
        ];
    }
}
