<?php

namespace App\Models;

use App\Enums\TellerSessionStatus;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TellerSession extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'user_id',
        'branch_id',
        'vault_id',
        'status',
        'opening_balance',
        'current_balance',
        'closing_balance',
        'total_cash_in',
        'total_cash_out',
        'transaction_count',
        'opened_at',
        'closed_at',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TellerSessionStatus::class,
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_cash_in' => 'decimal:2',
            'total_cash_out' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * @return HasMany<TellerTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(TellerTransaction::class);
    }

    public static function getActiveForUser(User $teller): ?static
    {
        return static::query()
            ->forUser($teller->id)
            ->open()
            ->first();
    }

    public function isOpen(): bool
    {
        return $this->status === TellerSessionStatus::Open;
    }

    #[Scope]
    protected function open($query)
    {
        return $query->where('status', TellerSessionStatus::Open);
    }

    #[Scope]
    protected function forUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
