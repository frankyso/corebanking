<?php

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasApproval
{
    public function initializeHasApproval(): void
    {
        $this->fillable = array_merge($this->fillable, [
            'approval_status',
            'created_by',
            'approved_by',
            'approved_at',
            'rejection_reason',
        ]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(User $approver): bool
    {
        if (! $this->canBeApprovedBy($approver)) {
            return false;
        }

        $this->update([
            'approval_status' => ApprovalStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function reject(User $approver, string $reason): bool
    {
        if (! $this->canBeApprovedBy($approver)) {
            return false;
        }

        $this->update([
            'approval_status' => ApprovalStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public function canBeApprovedBy(User $user): bool
    {
        if ($this->approval_status !== ApprovalStatus::Pending) {
            return false;
        }

        return $this->created_by !== $user->id;
    }

    public function isPending(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved;
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', ApprovalStatus::Pending);
    }
}
