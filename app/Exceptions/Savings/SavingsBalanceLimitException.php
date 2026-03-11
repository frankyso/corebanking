<?php

namespace App\Exceptions\Savings;

use App\Exceptions\DomainException;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;

class SavingsBalanceLimitException extends DomainException
{
    public static function belowMinimumOpeningBalance(SavingsProduct $product): static
    {
        return (new static(
            'Setoran awal minimal Rp '.number_format((float) $product->min_opening_balance, 0, ',', '.')
        ))
            ->withContext([
                'product_id' => $product->id,
                'min_opening_balance' => (float) $product->min_opening_balance,
            ]);
    }

    public static function belowMinimumBalance(SavingsAccount $account, float $remainingBalance): static
    {
        $minBalance = (float) $account->savingsProduct->min_balance;

        return (new static(
            'Saldo minimal Rp '.number_format($minBalance, 0, ',', '.')
        ))
            ->withContext([
                'account_id' => $account->id,
                'remaining_balance' => $remainingBalance,
                'min_balance' => $minBalance,
            ]);
    }

    public static function exceedsMaximumBalance(SavingsAccount $account, float $projectedBalance): static
    {
        return (new static('Saldo melebihi batas maksimal'))
            ->withContext([
                'account_id' => $account->id,
                'projected_balance' => $projectedBalance,
                'max_balance' => (float) $account->savingsProduct->max_balance,
            ]);
    }
}
