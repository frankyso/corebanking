<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Vault extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = [
        'code',
        'name',
        'branch_id',
        'balance',
        'minimum_balance',
        'maximum_balance',
        'is_active',
        'custodian_id',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'minimum_balance' => 'decimal:2',
            'maximum_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VaultTransaction::class);
    }

    public function tellerSessions(): HasMany
    {
        return $this->hasMany(TellerSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
