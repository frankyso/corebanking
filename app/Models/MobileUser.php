<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\MobileUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string|null $pin_hash
 * @property int $pin_attempts
 * @property Carbon|null $pin_locked_until
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
class MobileUser extends Authenticatable
{
    /** @use HasFactory<MobileUserFactory> */
    use HasApiTokens, HasFactory, HasMicrosecondTimestamps, Notifiable;

    protected $fillable = [
        'customer_id',
        'phone_number',
        'pin_hash',
        'pin_attempts',
        'pin_locked_until',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'pin_hash' => 'hashed',
            'pin_locked_until' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
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
     * @return HasMany<MobileDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    /**
     * @return HasMany<OtpVerification, $this>
     */
    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class);
    }

    /**
     * @return HasMany<MobileNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(MobileNotification::class);
    }

    /**
     * @return HasMany<TransferFavorite, $this>
     */
    public function transferFavorites(): HasMany
    {
        return $this->hasMany(TransferFavorite::class);
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until !== null && $this->pin_locked_until->isFuture();
    }

    public function incrementPinAttempts(): void
    {
        $this->increment('pin_attempts');

        if ($this->pin_attempts >= 5) {
            $this->update(['pin_locked_until' => now()->addMinutes(30)]);
        }
    }

    public function resetPinAttempts(): void
    {
        $this->update([
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);
    }
}
