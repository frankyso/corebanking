<?php

namespace App\Actions\Accounting;

use App\Actions\Accounting\Concerns\UpdatesGlBalances;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\ApprovalStatus;
use App\Enums\JournalStatus;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;

class CreateJournalEntry
{
    use UpdatesGlBalances;

    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function execute(CreateJournalData $dto): JournalEntry
    {
        $this->validateLines($dto->lines);

        return DB::transaction(function () use ($dto): JournalEntry {
            $journalNumber = $this->sequenceService->generateJournalNumber();

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'journal_date' => $dto->journalDate,
                'description' => $dto->description,
                'source' => $dto->source,
                'status' => $dto->autoPost ? JournalStatus::Posted : JournalStatus::Draft,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'total_debit' => 0,
                'total_credit' => 0,
                'branch_id' => $dto->branchId,
                'created_by' => $dto->creator->id,
                'approval_status' => $dto->autoPost ? ApprovalStatus::Approved : ApprovalStatus::Pending,
                'approved_by' => $dto->autoPost ? $dto->creator->id : null,
                'approved_at' => $dto->autoPost ? now() : null,
                'posted_at' => $dto->autoPost ? now() : null,
            ]);

            foreach ($dto->lines as $line) {
                $journal->lines()->create([
                    'chart_of_account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            $journal->recalculateTotals();

            if ($dto->autoPost) {
                $this->updateGlBalances($journal);
            }

            return $journal->fresh(['lines.chartOfAccount']);
        });
    }

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string}>  $lines
     */
    protected function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw UnbalancedJournalException::tooFewLines(count($lines));
        }

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);

            if ((float) $debit > 0 && (float) $credit > 0) {
                throw UnbalancedJournalException::lineHasBothDebitAndCredit();
            }

            if ((float) $debit == 0 && (float) $credit == 0) {
                throw UnbalancedJournalException::lineHasNoAmount();
            }

            $account = ChartOfAccount::find($line['account_id']);

            if (! $account || $account->is_header) {
                throw UnbalancedJournalException::invalidAccount($line['account_id']);
            }

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw UnbalancedJournalException::debitCreditMismatch($totalDebit, $totalCredit);
        }
    }
}
