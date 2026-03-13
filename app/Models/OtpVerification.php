<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expires_at
 * @property bool $is_used
 * @property int $attempts
 * @property OtpPurpose $purpose
 */
class OtpVerification extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'mobile_user_id',
        'phone_number',
        'otp_hash',
        'purpose',
        'is_used',
        'attempts',
        'expires_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'is_used' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MobileUser, $this>
     */
    public function mobileUser(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->is_used && ! $this->isExpired() && $this->attempts < 5;
    }

    public function markUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
