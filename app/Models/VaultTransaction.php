<?php

namespace App\Models;

use App\Enums\VaultTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultTransaction extends Model
{
    protected $fillable = [
        'reference_number',
        'vault_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'performed_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => VaultTransactionType::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
