<?php

namespace App\Exceptions\Accounting;

use App\Exceptions\DomainException;

class UnbalancedJournalException extends DomainException
{
    public static function debitCreditMismatch(string $totalDebit, string $totalCredit): static
    {
        return (new static("Total debit ({$totalDebit}) dan kredit ({$totalCredit}) tidak seimbang"))
            ->withContext([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);
    }

    public static function lineHasBothDebitAndCredit(): static
    {
        return new static('Baris jurnal tidak boleh memiliki debit dan kredit sekaligus');
    }

    public static function lineHasNoAmount(): static
    {
        return new static('Baris jurnal harus memiliki debit atau kredit');
    }

    public static function tooFewLines(int $count): static
    {
        return (new static('Jurnal harus memiliki minimal 2 baris'))
            ->withContext([
                'line_count' => $count,
            ]);
    }

    public static function invalidAccount(int $accountId): static
    {
        return (new static('Akun tidak valid atau merupakan akun header'))
            ->withContext([
                'account_id' => $accountId,
            ]);
    }
}
