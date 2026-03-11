<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DepositProduct extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'currency',
        'min_amount',
        'max_amount',
        'penalty_rate',
        'tax_rate',
        'tax_threshold',
        'is_active',
        'gl_deposit_id',
        'gl_interest_expense_id',
        'gl_interest_payable_id',
        'gl_tax_payable_id',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'penalty_rate' => 'decimal:5',
            'tax_rate' => 'decimal:5',
            'tax_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DepositProductRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(DepositProductRate::class);
    }

    /**
     * @return HasMany<DepositAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(DepositAccount::class);
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glDeposit(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_deposit_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glInterestExpense(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_interest_expense_id');
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    public function getRateForTenorAndAmount(int $tenorMonths, float $amount): ?DepositProductRate
    {
        return $this->rates()
            ->where('tenor_months', $tenorMonths)
            ->where('min_amount', '<=', $amount)
            ->where(fn (Builder $q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount))
            ->where('is_active', true)
            ->orderByDesc('min_amount')
            ->first();
    }
}
