<?php

namespace App\Actions\Deposit;

use App\Actions\Deposit\Concerns\CreatesDepositTransaction;
use App\Enums\DepositStatus;
use App\Exceptions\Deposit\DepositPledgedException;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\DepositAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EarlyWithdrawDeposit
{
    use CreatesDepositTransaction;

    public function execute(DepositAccount $account, User $performer): DepositAccount
    {
        if ($account->status !== DepositStatus::Active) {
            throw InvalidDepositStatusException::notActive($account);
        }

        if ($account->is_pledged) {
            throw DepositPledgedException::cannotWithdraw($account);
        }

        return DB::transaction(function () use ($account, $performer): DepositAccount {
            $product = $account->depositProduct;
            $penaltyRate = (float) $product->penalty_rate;
            $penaltyAmount = bcmul((string) $account->principal_amount, bcdiv((string) $penaltyRate, '100', 8), 2);

            if ((float) $penaltyAmount > 0) {
                $this->createTransaction(
                    account: $account,
                    type: 'penalty',
                    amount: (float) $penaltyAmount,
                    performer: $performer,
                    description: "Penalti pencairan dini ({$penaltyRate}%)",
                );
            }

            $this->createTransaction(
                account: $account,
                type: 'withdrawal',
                amount: (float) $account->principal_amount,
                performer: $performer,
                description: 'Pencairan deposito sebelum jatuh tempo',
            );

            $account->update([
                'status' => DepositStatus::Withdrawn,
            ]);

            return $account->fresh();
        });
    }
}
