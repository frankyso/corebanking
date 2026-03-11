<?php

namespace App\Actions\Accounting;

use App\Actions\Accounting\Concerns\UpdatesGlBalances;
use App\Enums\ApprovalStatus;
use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostJournalEntry
{
    use UpdatesGlBalances;

    public function execute(JournalEntry $journal, User $approver): JournalEntry
    {
        if ($journal->status !== JournalStatus::Draft) {
            throw InvalidJournalStatusException::notDraft($journal);
        }

        if (! $journal->isBalanced()) {
            throw InvalidJournalStatusException::notBalanced($journal);
        }

        if (! $journal->canBeApprovedBy($approver)) {
            throw InvalidJournalStatusException::selfApproval($journal, $approver);
        }

        return DB::transaction(function () use ($journal, $approver): JournalEntry {
            $journal->update([
                'status' => JournalStatus::Posted,
                'approval_status' => ApprovalStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            $this->updateGlBalances($journal);

            return $journal->fresh();
        });
    }
}
