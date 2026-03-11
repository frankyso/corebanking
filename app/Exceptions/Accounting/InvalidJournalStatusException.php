<?php

namespace App\Exceptions\Accounting;

use App\Exceptions\DomainException;
use App\Models\JournalEntry;
use App\Models\User;

class InvalidJournalStatusException extends DomainException
{
    public static function notDraft(JournalEntry $journal): static
    {
        return (new static('Jurnal harus berstatus Draft untuk diposting'))
            ->withContext([
                'journal_id' => $journal->id,
                'current_status' => $journal->status->value,
            ]);
    }

    public static function notPosted(JournalEntry $journal): static
    {
        return (new static('Hanya jurnal yang sudah diposting yang dapat dibatalkan'))
            ->withContext([
                'journal_id' => $journal->id,
                'current_status' => $journal->status->value,
            ]);
    }

    public static function notBalanced(JournalEntry $journal): static
    {
        return (new static('Total debit dan kredit tidak seimbang'))
            ->withContext([
                'journal_id' => $journal->id,
                'total_debit' => (string) $journal->total_debit,
                'total_credit' => (string) $journal->total_credit,
            ]);
    }

    public static function selfApproval(JournalEntry $journal, User $user): static
    {
        return (new static('Anda tidak dapat menyetujui jurnal yang Anda buat sendiri'))
            ->withContext([
                'journal_id' => $journal->id,
                'user_id' => $user->id,
            ]);
    }
}
