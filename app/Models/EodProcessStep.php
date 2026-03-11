<?php

namespace App\Models;

use App\Enums\EodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EodProcessStep extends Model
{
    protected $fillable = [
        'eod_process_id',
        'step_number',
        'step_name',
        'status',
        'records_processed',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => EodStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function eodProcess(): BelongsTo
    {
        return $this->belongsTo(EodProcess::class);
    }

    public function durationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
