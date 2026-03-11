<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlDailyBalance extends Model
{
    use HasMicrosecondTimestamps;

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

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    #[Scope]
    protected function forDate($query, $date)
    {
        return $query->where('balance_date', $date);
    }
}
