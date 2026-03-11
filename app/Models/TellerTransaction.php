<?php

namespace App\Models;

use App\Enums\TellerTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TellerTransaction extends Model
{
    protected $fillable = [
        'reference_number',
        'teller_session_id',
        'transaction_type',
        'amount',
        'teller_balance_before',
        'teller_balance_after',
        'direction',
        'description',
        'customer_id',
        'reference_type',
        'reference_id',
        'is_reversed',
        'reversed_by_id',
        'needs_authorization',
        'authorized_by',
        'authorized_at',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => TellerTransactionType::class,
            'amount' => 'decimal:2',
            'teller_balance_before' => 'decimal:2',
            'teller_balance_after' => 'decimal:2',
            'is_reversed' => 'boolean',
            'needs_authorization' => 'boolean',
            'authorized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TellerSession, $this>
     */
    public function tellerSession(): BelongsTo
    {
        return $this->belongsTo(TellerSession::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * @return BelongsTo<TellerTransaction, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function isCashIn(): bool
    {
        return $this->direction === 'in';
    }

    public function isCashOut(): bool
    {
        return $this->direction === 'out';
    }
}
