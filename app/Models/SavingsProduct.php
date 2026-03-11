<?php

namespace App\Models;

use App\Enums\InterestCalcMethod;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SavingsProduct extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'currency',
        'interest_calc_method',
        'interest_rate',
        'min_opening_balance',
        'min_balance',
        'max_balance',
        'admin_fee_monthly',
        'closing_fee',
        'dormant_fee',
        'dormant_period_days',
        'tax_rate',
        'tax_threshold',
        'is_active',
        'gl_savings_id',
        'gl_interest_expense_id',
        'gl_interest_payable_id',
        'gl_admin_fee_income_id',
        'gl_tax_payable_id',
    ];

    protected function casts(): array
    {
        return [
            'interest_calc_method' => InterestCalcMethod::class,
            'interest_rate' => 'decimal:5',
            'min_opening_balance' => 'decimal:2',
            'min_balance' => 'decimal:2',
            'max_balance' => 'decimal:2',
            'admin_fee_monthly' => 'decimal:2',
            'closing_fee' => 'decimal:2',
            'dormant_fee' => 'decimal:2',
            'tax_rate' => 'decimal:5',
            'tax_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SavingsAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glSavings(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_savings_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glInterestExpense(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_interest_expense_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glInterestPayable(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_interest_payable_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glAdminFeeIncome(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_admin_fee_income_id');
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glTaxPayable(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_tax_payable_id');
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }
}
