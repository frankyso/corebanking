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

class DepositToSavings
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
            throw InsufficientSavingsBalanceException::invalidAmount('setoran');
        }

        $product = $account->savingsProduct;
        if ($product->max_balance && bcadd($account->balance, (string) $amount, 2) > (float) $product->max_balance) {
            throw SavingsBalanceLimitException::exceedsMaximumBalance(
                $account,
                (float) bcadd($account->balance, (string) $amount, 2),
            );
        }

        return DB::transaction(function () use ($account, $amount, $performer, $description): SavingsTransaction {
            $transaction = $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Deposit,
                amount: $amount,
                performer: $performer,
                description: $description ?? 'Setoran tunai',
            );

            $account->update([
                'balance' => bcadd($account->balance, (string) $amount, 2),
                'last_transaction_at' => now(),
                'dormant_at' => null,
                'status' => SavingsAccountStatus::Active,
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
