<?php

namespace App\Models;

use App\Enums\EodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EodProcess extends Model
{
    protected $fillable = [
        'process_date',
        'status',
        'total_steps',
        'completed_steps',
        'started_at',
        'completed_at',
        'error_message',
        'started_by',
    ];

    protected function casts(): array
    {
        return [
            'process_date' => 'date',
            'status' => EodStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EodProcessStep::class)->orderBy('step_number');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function isRunning(): bool
    {
        return $this->status === EodStatus::Running;
    }

    public function isCompleted(): bool
    {
        return $this->status === EodStatus::Completed;
    }

    public function progressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return (int) round(($this->completed_steps / $this->total_steps) * 100);
    }
}
