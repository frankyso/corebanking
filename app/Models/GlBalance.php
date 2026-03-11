<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlBalance extends Model
{
    protected $fillable = [
        'chart_of_account_id',
        'branch_id',
        'period_year',
        'period_month',
        'opening_balance',
        'debit_total',
        'credit_total',
        'closing_balance',
    ];

    protected function casts(): array
    {
        return [
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

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }
}
