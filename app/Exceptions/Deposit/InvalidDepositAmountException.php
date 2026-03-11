<?php

namespace App\Exceptions\Deposit;

use App\Exceptions\DomainException;
use App\Models\DepositProduct;

class InvalidDepositAmountException extends DomainException
{
    public static function belowMinimum(DepositProduct $product): static
    {
        return (new static(
            'Nominal minimal deposito Rp '.number_format((float) $product->min_amount, 0, ',', '.')
        ))->withContext([
            'product_id' => $product->id,
            'min_amount' => $product->min_amount,
        ]);
    }

    public static function aboveMaximum(DepositProduct $product): static
    {
        return (new static(
            'Nominal maksimal deposito Rp '.number_format((float) $product->max_amount, 0, ',', '.')
        ))->withContext([
            'product_id' => $product->id,
            'max_amount' => $product->max_amount,
        ]);
    }

    public static function noRateAvailable(int $tenorMonths): static
    {
        return (new static(
            "Tidak ada suku bunga untuk tenor {$tenorMonths} bulan dengan nominal tersebut"
        ))->withContext([
            'tenor_months' => $tenorMonths,
        ]);
    }
}
