<?php

namespace App\Exceptions\Loan;

use App\Exceptions\DomainException;
use App\Models\LoanProduct;

class InvalidLoanAmountException extends DomainException
{
    public static function belowMinimum(LoanProduct $product, float $amount): static
    {
        return (new static('Jumlah pinjaman kurang dari minimum'))
            ->withContext([
                'product_id' => $product->id,
                'min_amount' => (float) $product->min_amount,
                'requested_amount' => $amount,
            ]);
    }

    public static function aboveMaximum(LoanProduct $product, float $amount): static
    {
        return (new static('Jumlah pinjaman melebihi maksimum'))
            ->withContext([
                'product_id' => $product->id,
                'max_amount' => (float) $product->max_amount,
                'requested_amount' => $amount,
            ]);
    }
}
