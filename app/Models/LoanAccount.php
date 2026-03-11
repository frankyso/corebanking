<?php

namespace App\Models;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LoanAccount extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'account_number',
        'customer_id',
        'loan_product_id',
        'loan_application_id',
        'branch_id',
        'status',
        'principal_amount',
        'interest_rate',
        'tenor_months',
        'outstanding_principal',
        'outstanding_interest',
        'accrued_interest',
        'total_principal_paid',
        'total_interest_paid',
        'total_penalty_paid',
        'disbursement_date',
        'maturity_date',
        'last_payment_date',
        'dpd',
        'collectibility',
        'ckpn_amount',
        'loan_officer_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'collectibility' => Collectibility::class,
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:5',
            'outstanding_principal' => 'decimal:2',
            'outstanding_interest' => 'decimal:2',
            'accrued_interest' => 'decimal:2',
            'total_principal_paid' => 'decimal:2',
            'total_interest_paid' => 'decimal:2',
            'total_penalty_paid' => 'decimal:2',
            'ckpn_amount' => 'decimal:2',
            'disbursement_date' => 'date',
            'maturity_date' => 'date',
            'last_payment_date' => 'date',
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
     * @return BelongsTo<LoanApplication, $this>
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
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
     * @return HasMany<LoanSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class);
    }

    /**
     * @return HasMany<LoanPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    /**
     * @return HasMany<LoanCollateral, $this>
     */
    public function collaterals(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue]);
    }

    #[Scope]
    protected function byCollectibility($query, Collectibility $collectibility)
    {
        return $query->where('collectibility', $collectibility);
    }

    public function getNextUnpaidSchedule(): ?LoanSchedule
    {
        return $this->schedules()->where('is_paid', false)->orderBy('installment_number')->first();
    }

    public function getOverdueSchedules(): Collection
    {
        return $this->schedules()->where('is_paid', false)->where('due_date', '<', now())->orderBy('installment_number')->get();
    }
}
