<?php

namespace App\Models;

use App\Enums\SavingsAccountStatus;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SavingsAccount extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasMicrosecondTimestamps, SoftDeletes;

    protected $fillable = [
        'account_number',
        'customer_id',
        'savings_product_id',
        'branch_id',
        'status',
        'balance',
        'hold_amount',
        'available_balance',
        'accrued_interest',
        'total_tax_paid',
        'opened_at',
        'closed_at',
        'last_interest_posted_at',
        'last_transaction_at',
        'dormant_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SavingsAccountStatus::class,
            'balance' => 'decimal:2',
            'hold_amount' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'accrued_interest' => 'decimal:2',
            'total_tax_paid' => 'decimal:2',
            'opened_at' => 'date',
            'closed_at' => 'date',
            'last_interest_posted_at' => 'date',
            'last_transaction_at' => 'date',
            'dormant_at' => 'date',
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
     * @return BelongsTo<SavingsProduct, $this>
     */
    public function savingsProduct(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SavingsTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    /**
     * @return HasMany<SavingsInterestAccrual, $this>
     */
    public function interestAccruals(): HasMany
    {
        return $this->hasMany(SavingsInterestAccrual::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('status', SavingsAccountStatus::Active);
    }

    #[Scope]
    protected function byStatus($query, SavingsAccountStatus $status)
    {
        return $query->where('status', $status);
    }

    public function recalculateAvailableBalance(): void
    {
        $this->available_balance = bcsub($this->balance, $this->hold_amount, 2);
        $this->save();
    }
}
