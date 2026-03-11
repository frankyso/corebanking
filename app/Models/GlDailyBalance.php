<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlDailyBalance extends Model
{
    protected $fillable = [
        'chart_of_account_id',
        'branch_id',
        'balance_date',
        'opening_balance',
        'debit_total',
        'credit_total',
        'closing_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance_date' => 'date',
            'opening_balance' => 'decimal:2',
            'debit_total' => 'decimal:2',
            'credit_total' => 'decimal:2',
            'closing_balance' => 'decimal:2',
        ];
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('balance_date', $date);
    }
}
