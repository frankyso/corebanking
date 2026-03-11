<?php

namespace App\Models;

use App\Enums\SavingsTransactionType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SavingsTransaction extends Model implements AuditableContract
{
    use Auditable, HasMicrosecondTimestamps;

    protected $fillable = [
        'reference_number',
        'savings_account_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'transaction_date',
        'value_date',
        'performed_by',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'is_reversed',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => SavingsTransactionType::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'date',
            'value_date' => 'date',
            'reversed_at' => 'datetime',
            'is_reversed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
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
    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
