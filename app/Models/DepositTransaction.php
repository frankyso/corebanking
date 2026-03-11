<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositTransaction extends Model
{
    protected $fillable = [
        'reference_number',
        'deposit_account_id',
        'transaction_type',
        'amount',
        'description',
        'transaction_date',
        'performed_by',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<DepositAccount, $this>
     */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(DepositAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
