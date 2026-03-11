<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsInterestAccrual extends Model
{
    protected $fillable = [
        'savings_account_id',
        'accrual_date',
        'balance',
        'interest_rate',
        'accrued_amount',
        'tax_amount',
        'is_posted',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'accrual_date' => 'date',
            'balance' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'accrued_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'is_posted' => 'boolean',
            'posted_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }
}
