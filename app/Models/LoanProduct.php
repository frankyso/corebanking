<?php

namespace App\Models;

use App\Enums\InterestType;
use App\Enums\LoanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LoanProduct extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'loan_type',
        'interest_type',
        'min_amount',
        'max_amount',
        'interest_rate',
        'min_tenor_months',
        'max_tenor_months',
        'admin_fee_rate',
        'provision_fee_rate',
        'insurance_rate',
        'penalty_rate',
        'is_active',
        'gl_loan_id',
        'gl_interest_income_id',
        'gl_interest_receivable_id',
        'gl_fee_income_id',
        'gl_provision_id',
    ];

    protected function casts(): array
    {
        return [
            'loan_type' => LoanType::class,
            'interest_type' => InterestType::class,
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'admin_fee_rate' => 'decimal:5',
            'provision_fee_rate' => 'decimal:5',
            'insurance_rate' => 'decimal:5',
            'penalty_rate' => 'decimal:5',
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(LoanAccount::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function glLoan(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_loan_id');
    }

    public function glInterestIncome(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_interest_income_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
