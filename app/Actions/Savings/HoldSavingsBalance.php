<?php

namespace App\Actions\Savings;

use App\Actions\Savings\Concerns\CreatesSavingsTransaction;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HoldSavingsBalance
{
    use CreatesSavingsTransaction;

    public function execute(SavingsAccount $account, float $amount, User $performer): void
    {
        $this->validateActiveAccount($account);

        if ($amount > (float) $account->available_balance) {
            throw InsufficientSavingsBalanceException::forHold($account, $amount);
        }

        DB::transaction(function () use ($account, $amount, $performer): void {
            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Hold,
                amount: $amount,
                performer: $performer,
                description: 'Pemblokiran saldo Rp '.number_format($amount, 0, ',', '.'),
            );

            $account->update([
                'hold_amount' => bcadd($account->hold_amount, (string) $amount, 2),
            ]);
            $account->recalculateAvailableBalance();
        });
    }

    private function validateActiveAccount(SavingsAccount $account): void
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw InvalidSavingsAccountStatusException::notActive($account);
        }
    }
}
