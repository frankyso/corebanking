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

class UnholdSavingsBalance
{
    use CreatesSavingsTransaction;

    public function execute(SavingsAccount $account, float $amount, User $performer): void
    {
        $this->validateActiveAccount($account);

        if ($amount > (float) $account->hold_amount) {
            throw InsufficientSavingsBalanceException::unholdExceedsHeldAmount($account, $amount);
        }

        DB::transaction(function () use ($account, $amount, $performer): void {
            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Unhold,
                amount: $amount,
                performer: $performer,
                description: 'Pembukaan blokir Rp '.number_format($amount, 0, ',', '.'),
            );

            $account->update([
                'hold_amount' => bcsub($account->hold_amount, (string) $amount, 2),
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
