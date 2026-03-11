<?php

namespace App\Exceptions\Loan;

use App\Exceptions\DomainException;
use App\Models\LoanProduct;

class InvalidLoanTenorException extends DomainException
{
    public static function outOfRange(LoanProduct $product, int $tenor): static
    {
        return (new static("Tenor harus antara {$product->min_tenor_months} - {$product->max_tenor_months} bulan"))
            ->withContext([
                'product_id' => $product->id,
                'min_tenor' => $product->min_tenor_months,
                'max_tenor' => $product->max_tenor_months,
                'requested_tenor' => $tenor,
            ]);
    }
}
