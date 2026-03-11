<?php

namespace App\Actions\Deposit;

use App\Actions\Deposit\Concerns\CreatesDepositTransaction;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\DepositAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessDepositMaturity
{
    use CreatesDepositTransaction;

    public function __construct(
        private RolloverDeposit $rolloverDeposit,
    ) {}

    public function execute(DepositAccount $account, User $performer): DepositAccount
    {
        if ($account->status !== DepositStatus::Active) {
            throw InvalidDepositStatusException::notActive($account);
        }

        if (! $account->isMatured()) {
            throw InvalidDepositStatusException::notMatured($account);
        }

        return DB::transaction(function () use ($account, $performer): DepositAccount {
            if ($account->interest_payment_method === InterestPaymentMethod::Maturity) {
                $totalInterest = $this->calculateTotalInterest(
                    (float) $account->principal_amount,
                    (float) $account->interest_rate,
                    $account->tenor_months,
                );
                $taxAmount = $this->calculateTax($account->depositProduct, $totalInterest);
                $netInterest = bcsub((string) $totalInterest, (string) $taxAmount, 2);

                $this->createTransaction(
                    account: $account,
                    type: 'interest_payment',
                    amount: (float) $netInterest,
                    performer: $performer,
                    description: 'Pembayaran bunga jatuh tempo',
                );

                if ($taxAmount > 0) {
                    $this->createTransaction(
                        account: $account,
                        type: 'tax',
                        amount: $taxAmount,
                        performer: $performer,
                        description: 'Pajak bunga deposito',
                    );
                }

                $account->update([
                    'total_interest_paid' => bcadd($account->total_interest_paid, $netInterest, 2),
                    'total_tax_paid' => bcadd($account->total_tax_paid, (string) $taxAmount, 2),
                    'last_interest_paid_at' => now(),
                ]);
            }

            if ($account->rollover_type === RolloverType::None) {
                $account->update(['status' => DepositStatus::Matured]);

                return $account->fresh();
            }

            return $this->rolloverDeposit->execute($account, $performer);
        });
    }
}
