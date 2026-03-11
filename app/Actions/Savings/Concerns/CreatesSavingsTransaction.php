<?php

namespace App\Actions\Savings\Concerns;

use App\Enums\SavingsTransactionType;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;

trait CreatesSavingsTransaction
{
    private function createTransaction(
        SavingsAccount $account,
        SavingsTransactionType $type,
        float $amount,
        User $performer,
        ?string $description = null,
    ): SavingsTransaction {
        $balanceBefore = (float) $account->balance;
        $balanceAfter = $type->isCredit()
            ? bcadd((string) $balanceBefore, (string) $amount, 2)
            : bcsub((string) $balanceBefore, (string) $amount, 2);

        $referenceNumber = $this->generateTransactionReference();

        return SavingsTransaction::create([
            'reference_number' => $referenceNumber,
            'savings_account_id' => $account->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'value_date' => now()->toDateString(),
            'performed_by' => $performer->id,
        ]);
    }

    private function generateTransactionReference(): string
    {
        return 'TRX'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
