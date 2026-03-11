<?php

namespace App\Models;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loanOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue]);
    }

    public function scopeByCollectibility($query, Collectibility $collectibility)
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
