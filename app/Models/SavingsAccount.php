<?php

namespace App\Models;

use App\Enums\SavingsAccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SavingsAccount extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

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
            'opened_at' => 'date',
            'closed_at' => 'date',
            'last_interest_posted_at' => 'date',
            'last_transaction_at' => 'date',
            'dormant_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function savingsProduct(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function interestAccruals(): HasMany
    {
        return $this->hasMany(SavingsInterestAccrual::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SavingsAccountStatus::Active);
    }

    public function scopeByStatus($query, SavingsAccountStatus $status)
    {
        return $query->where('status', $status);
    }

    public function recalculateAvailableBalance(): void
    {
        $this->available_balance = bcsub($this->balance, $this->hold_amount, 2);
        $this->save();
    }
}
