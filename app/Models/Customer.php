<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Customer extends Model implements AuditableContract
{
    use Auditable, HasApproval, HasFactory, SoftDeletes;

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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function individualDetail(): HasOne
    {
        return $this->hasOne(IndividualDetail::class);
    }

    public function corporateDetail(): HasOne
    {
        return $this->hasOne(CorporateDetail::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->customer_type === CustomerType::Individual) {
            return $this->individualDetail?->full_name ?? $this->cif_number;
        }

        return $this->corporateDetail?->company_name ?? $this->cif_number;
    }

    public function scopeActive($query)
    {
        return $query->where('status', CustomerStatus::Active);
    }

    public function scopeByType($query, CustomerType $type)
    {
        return $query->where('customer_type', $type);
    }
}
