<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositProductRate extends Model
{
    protected $fillable = [
        'deposit_product_id',
        'tenor_months',
        'min_amount',
        'max_amount',
        'interest_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'is_active' => 'boolean',
        ];
    }

    public function depositProduct(): BelongsTo
    {
        return $this->belongsTo(DepositProduct::class);
    }
}
