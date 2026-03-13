<?php

namespace App\Models;

use App\Enums\DevicePlatform;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property DevicePlatform $platform
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 */
class MobileDevice extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'mobile_user_id',
        'device_id',
        'device_name',
        'platform',
        'fcm_token',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => DevicePlatform::class,
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MobileUser, $this>
     */
    public function mobileUser(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class);
    }
}
