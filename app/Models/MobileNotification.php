<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property NotificationType $type
 * @property array<string, mixed>|null $data
 * @property bool $is_read
 * @property Carbon|null $read_at
 */
class MobileNotification extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'mobile_user_id',
        'title',
        'body',
        'type',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MobileUser, $this>
     */
    public function mobileUser(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
