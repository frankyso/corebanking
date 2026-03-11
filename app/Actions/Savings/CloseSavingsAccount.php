<?php

namespace App\Actions\Savings;

use App\Actions\Savings\Concerns\CreatesSavingsTransaction;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CloseSavingsAccount
{
    use CreatesSavingsTransaction;

    public function execute(SavingsAccount $account, User $performer): ?SavingsTransaction
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw InvalidSavingsAccountStatusException::cannotClose($account);
        }

        if ((float) $account->hold_amount > 0) {
            throw InvalidSavingsAccountStatusException::hasHoldAmount($account);
        }

        return DB::transaction(function () use ($account, $performer): ?SavingsTransaction {
            $remainingBalance = (float) $account->balance;
            $transaction = null;

            if ($remainingBalance > 0) {
                $transaction = $this->createTransaction(
                    account: $account,
                    type: SavingsTransactionType::Closing,
                    amount: $remainingBalance,
                    performer: $performer,
                    description: 'Penutupan rekening',
                );
            }

            $account->update([
                'balance' => 0,
                'available_balance' => 0,
                'status' => SavingsAccountStatus::Closed,
                'closed_at' => now(),
            ]);

            return $transaction;
        });
    }
}
