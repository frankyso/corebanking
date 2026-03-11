<?php

namespace App\Actions\Accounting;

use App\DTOs\Accounting\CreateJournalData;
use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReverseJournalEntry
{
    public function __construct(
        private CreateJournalEntry $createJournalEntry,
    ) {}

    public function execute(JournalEntry $journal, User $reverser, string $reason): JournalEntry
    {
        if ($journal->status !== JournalStatus::Posted) {
            throw InvalidJournalStatusException::notPosted($journal);
        }

        return DB::transaction(function () use ($journal, $reverser, $reason): JournalEntry {
            $reversalLines = [];
            foreach ($journal->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->chart_of_account_id,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                    'description' => "Reversal: {$line->description}",
                ];
            }

            $reversalJournal = $this->createJournalEntry->execute(new CreateJournalData(
                journalDate: now(),
                description: "Pembatalan jurnal {$journal->journal_number}: {$reason}",
                source: $journal->source,
                lines: $reversalLines,
                creator: $reverser,
                branchId: $journal->branch_id,
                referenceType: 'reversal',
                referenceId: $journal->id,
                autoPost: true,
            ));

            $journal->update([
                'status' => JournalStatus::Reversed,
                'reversed_by' => $reverser->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_journal_id' => $reversalJournal->id,
            ]);

            return $journal->fresh();
        });
    }
}
