<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property-read string $display_name
 */
class Customer extends Model implements AuditableContract
{
    use Auditable, HasApproval, HasFactory, HasMicrosecondTimestamps, SoftDeletes;

    protected $fillable = [
        'cif_number',
        'customer_type',
        'status',
        'risk_rating',
        'branch_id',
        'approval_status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'status' => CustomerStatus::class,
            'risk_rating' => RiskRating::class,
            'approval_status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasOne<IndividualDetail, $this>
     */
    public function individualDetail(): HasOne
    {
        return $this->hasOne(IndividualDetail::class);
    }

    /**
     * @return HasOne<CorporateDetail, $this>
     */
    public function corporateDetail(): HasOne
    {
        return $this->hasOne(CorporateDetail::class);
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return HasMany<CustomerPhone, $this>
     */
    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class);
    }

    /**
     * @return HasMany<CustomerDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    /**
     * @return HasMany<SavingsAccount, $this>
     */
    public function savingsAccounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    /**
     * @return HasMany<DepositAccount, $this>
     */
    public function depositAccounts(): HasMany
    {
        return $this->hasMany(DepositAccount::class);
    }

    /**
     * @return HasMany<LoanAccount, $this>
     */
    public function loanAccounts(): HasMany
    {
        return $this->hasMany(LoanAccount::class);
    }

    /**
     * @return HasMany<LoanApplication, $this>
     */
    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->customer_type === CustomerType::Individual) {
                return $this->individualDetail?->full_name ?? $this->cif_number;
            }

            return $this->corporateDetail?->company_name ?? $this->cif_number;
        });
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('status', CustomerStatus::Active);
    }

    #[Scope]
    protected function byType($query, CustomerType $type)
    {
        return $query->where('customer_type', $type);
    }

    public function block(): void
    {
        $this->update(['status' => CustomerStatus::Blocked]);
    }

    public function unblock(): void
    {
        $this->update(['status' => CustomerStatus::Active]);
    }

    public function deactivate(): void
    {
        $this->update(['status' => CustomerStatus::Inactive]);
    }

    public function markClosed(): void
    {
        $this->update(['status' => CustomerStatus::Closed]);
    }

    public static function checkDuplicateNik(string $nik, ?int $excludeCustomerId = null): bool
    {
        $query = IndividualDetail::where('nik', $nik);

        if ($excludeCustomerId) {
            $query->where('customer_id', '!=', $excludeCustomerId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function calculateRiskRating(array $data): RiskRating
    {
        $nationality = $data['nationality'] ?? 'IDN';
        $monthlyIncome = (float) ($data['monthly_income'] ?? 0);

        if ($nationality !== 'IDN' || $monthlyIncome > 500_000_000) {
            return RiskRating::High;
        }

        if ($monthlyIncome > 100_000_000) {
            return RiskRating::Medium;
        }

        return RiskRating::Low;
    }
}
