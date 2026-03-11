<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DepositAccount extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'account_number',
        'customer_id',
        'deposit_product_id',
        'branch_id',
        'status',
        'principal_amount',
        'interest_rate',
        'tenor_months',
        'interest_payment_method',
        'rollover_type',
        'placement_date',
        'maturity_date',
        'last_interest_paid_at',
        'accrued_interest',
        'total_interest_paid',
        'total_tax_paid',
        'is_pledged',
        'pledge_reference',
        'savings_account_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'interest_payment_method' => InterestPaymentMethod::class,
            'rollover_type' => RolloverType::class,
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'accrued_interest' => 'decimal:2',
            'total_interest_paid' => 'decimal:2',
            'total_tax_paid' => 'decimal:2',
            'placement_date' => 'date',
            'maturity_date' => 'date',
            'last_interest_paid_at' => 'date',
            'is_pledged' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<DepositProduct, $this>
     */
    public function depositProduct(): BelongsTo
    {
        return $this->belongsTo(DepositProduct::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DepositTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    /**
     * @return HasMany<DepositInterestAccrual, $this>
     */
    public function interestAccruals(): HasMany
    {
        return $this->hasMany(DepositInterestAccrual::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('status', DepositStatus::Active);
    }

    #[Scope]
    protected function maturing($query, $date)
    {
        return $query->where('maturity_date', '<=', $date)->where('status', DepositStatus::Active);
    }

    public function isMatured(): bool
    {
        if ($this->maturity_date->isPast()) {
            return true;
        }

        return (bool) $this->maturity_date->isToday();
    }

    public function daysToMaturity(): int
    {
        return max(0, now()->diffInDays($this->maturity_date, false));
    }
}
