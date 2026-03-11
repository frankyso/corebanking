<?php

namespace App\Actions\Deposit\Concerns;

use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositTransaction;
use App\Models\User;

trait CreatesDepositTransaction
{
    protected function createTransaction(
        DepositAccount $account,
        string $type,
        float $amount,
        User $performer,
        ?string $description = null,
    ): DepositTransaction {
        return DepositTransaction::create([
            'reference_number' => $this->generateTransactionReference(),
            'deposit_account_id' => $account->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'performed_by' => $performer->id,
        ]);
    }

    protected function generateTransactionReference(): string
    {
        return 'DEP'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function calculateTotalInterest(float $principal, float $rate, int $tenorMonths): float
    {
        $annualInterest = bcmul((string) $principal, bcdiv((string) $rate, '100', 8), 2);

        return (float) bcmul($annualInterest, bcdiv((string) $tenorMonths, '12', 8), 2);
    }

    protected function calculateTax(DepositProduct $product, float $interestAmount): float
    {
        $taxRate = (float) $product->tax_rate;
        if ($taxRate <= 0) {
            return 0;
        }

        return (float) bcmul((string) $interestAmount, bcdiv((string) $taxRate, '100', 8), 2);
    }
}
