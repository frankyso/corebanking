<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LoanApplication extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasMicrosecondTimestamps, SoftDeletes;

    protected $fillable = [
        'application_number',
        'customer_id',
        'loan_product_id',
        'branch_id',
        'status',
        'requested_amount',
        'approved_amount',
        'requested_tenor_months',
        'approved_tenor_months',
        'interest_rate',
        'purpose',
        'notes',
        'loan_officer_id',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'disbursed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanApplicationStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
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
     * @return BelongsTo<LoanProduct, $this>
     */
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
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
    public function loanOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<LoanCollateral, $this>
     */
    public function collaterals(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    #[Scope]
    protected function pending($query)
    {
        return $query->whereIn('status', [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview]);
    }
}
