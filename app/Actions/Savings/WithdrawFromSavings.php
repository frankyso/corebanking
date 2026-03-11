<?php

namespace App\Actions\Savings;

use App\Actions\Savings\Concerns\CreatesSavingsTransaction;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WithdrawFromSavings
{
    use CreatesSavingsTransaction;

    public function execute(
        SavingsAccount $account,
        float $amount,
        User $performer,
        ?string $description = null,
    ): SavingsTransaction {
        $this->validateActiveAccount($account);

        if ($amount <= 0) {
            throw InsufficientSavingsBalanceException::invalidAmount('penarikan');
        }

        if ($amount > (float) $account->available_balance) {
            throw InsufficientSavingsBalanceException::forWithdrawal($account, $amount);
        }

        $product = $account->savingsProduct;
        $remainingBalance = bcsub($account->balance, (string) $amount, 2);
        if ((float) $remainingBalance < (float) $product->min_balance) {
            throw SavingsBalanceLimitException::belowMinimumBalance($account, (float) $remainingBalance);
        }

        return DB::transaction(function () use ($account, $amount, $performer, $description): SavingsTransaction {
            $transaction = $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Withdrawal,
                amount: $amount,
                performer: $performer,
                description: $description ?? 'Penarikan tunai',
            );

            $account->update([
                'balance' => bcsub($account->balance, (string) $amount, 2),
                'last_transaction_at' => now(),
            ]);
            $account->recalculateAvailableBalance();

            return $transaction;
        });
    }

    private function validateActiveAccount(SavingsAccount $account): void
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw InvalidSavingsAccountStatusException::notActive($account);
        }
    }
}
