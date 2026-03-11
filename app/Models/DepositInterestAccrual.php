<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositInterestAccrual extends Model
{
    protected $fillable = [
        'deposit_account_id',
        'accrual_date',
        'principal',
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
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'accrued_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'is_posted' => 'boolean',
            'posted_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<DepositAccount, $this>
     */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(DepositAccount::class);
    }
}
