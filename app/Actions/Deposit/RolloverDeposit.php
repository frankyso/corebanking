<?php

namespace App\Actions\Deposit;

use App\Actions\Deposit\Concerns\CreatesDepositTransaction;
use App\Enums\DepositStatus;
use App\Enums\RolloverType;
use App\Models\DepositAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolloverDeposit
{
    use CreatesDepositTransaction;

    public function execute(DepositAccount $account, User $performer): DepositAccount
    {
        return DB::transaction(function () use ($account, $performer): DepositAccount {
            $product = $account->depositProduct;
            $newPrincipal = (float) $account->principal_amount;

            if ($account->rollover_type === RolloverType::PrincipalAndInterest) {
                $unpaidInterest = $this->calculateTotalInterest(
                    (float) $account->principal_amount,
                    (float) $account->interest_rate,
                    $account->tenor_months,
                );
                $taxOnUnpaid = $this->calculateTax($product, $unpaidInterest);
                $netUnpaid = bcsub((string) $unpaidInterest, (string) $taxOnUnpaid, 2);
                $newPrincipal = (float) bcadd((string) $newPrincipal, $netUnpaid, 2);
            }

            $rate = $product->getRateForTenorAndAmount($account->tenor_months, $newPrincipal);
            $newRate = $rate ? (float) $rate->interest_rate : (float) $account->interest_rate;

            $newPlacementDate = $account->maturity_date;
            $newMaturityDate = $newPlacementDate->copy()->addMonths($account->tenor_months);

            $account->update([
                'status' => DepositStatus::Rolled,
            ]);

            $this->createTransaction(
                account: $account,
                type: 'rollover',
                amount: $newPrincipal,
                performer: $performer,
                description: 'Perpanjangan deposito otomatis',
            );

            $account->update([
                'status' => DepositStatus::Active,
                'principal_amount' => $newPrincipal,
                'interest_rate' => $newRate,
                'placement_date' => $newPlacementDate,
                'maturity_date' => $newMaturityDate,
                'accrued_interest' => 0,
                'last_interest_paid_at' => null,
            ]);

            return $account->fresh();
        });
    }
}
