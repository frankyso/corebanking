<?php

namespace App\Actions\Deposit;

use App\Actions\Deposit\Concerns\CreatesDepositTransaction;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Exceptions\Deposit\InvalidDepositAmountException;
use App\Models\DepositAccount;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;

class PlaceDeposit
{
    use CreatesDepositTransaction;

    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function execute(PlaceDepositData $dto): DepositAccount
    {
        if ($dto->principalAmount < (float) $dto->product->min_amount) {
            throw InvalidDepositAmountException::belowMinimum($dto->product);
        }

        if ($dto->product->max_amount && $dto->principalAmount > (float) $dto->product->max_amount) {
            throw InvalidDepositAmountException::aboveMaximum($dto->product);
        }

        $rate = $dto->product->getRateForTenorAndAmount($dto->tenorMonths, $dto->principalAmount);
        if (! $rate) {
            throw InvalidDepositAmountException::noRateAvailable($dto->tenorMonths);
        }

        return DB::transaction(function () use ($dto, $rate): DepositAccount {
            $branchCode = $dto->performer->branch?->code ?? '001';
            $accountNumber = $this->sequenceService->generateAccountNumber($dto->product->code, $branchCode);
            $placement = $dto->placementDate ?? now();
            $maturity = $placement->copy()->addMonths($dto->tenorMonths);

            $account = DepositAccount::create([
                'account_number' => $accountNumber,
                'customer_id' => $dto->customerId,
                'deposit_product_id' => $dto->product->id,
                'branch_id' => $dto->branchId,
                'status' => DepositStatus::Active,
                'principal_amount' => $dto->principalAmount,
                'interest_rate' => $rate->interest_rate,
                'tenor_months' => $dto->tenorMonths,
                'interest_payment_method' => $dto->interestPaymentMethod,
                'rollover_type' => $dto->rolloverType,
                'placement_date' => $placement,
                'maturity_date' => $maturity,
                'accrued_interest' => 0,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
                'is_pledged' => false,
                'savings_account_id' => $dto->savingsAccountId,
                'created_by' => $dto->performer->id,
            ]);

            $this->createTransaction(
                account: $account,
                type: 'placement',
                amount: $dto->principalAmount,
                performer: $dto->performer,
                description: 'Penempatan deposito',
            );

            if ($dto->interestPaymentMethod === InterestPaymentMethod::Upfront) {
                $totalInterest = $this->calculateTotalInterest($dto->principalAmount, (float) $rate->interest_rate, $dto->tenorMonths);
                $taxAmount = $this->calculateTax($dto->product, $totalInterest);
                $netInterest = bcsub((string) $totalInterest, (string) $taxAmount, 2);

                $this->createTransaction(
                    account: $account,
                    type: 'interest_payment',
                    amount: (float) $netInterest,
                    performer: $dto->performer,
                    description: 'Pembayaran bunga di muka',
                );

                if ($taxAmount > 0) {
                    $this->createTransaction(
                        account: $account,
                        type: 'tax',
                        amount: $taxAmount,
                        performer: $dto->performer,
                        description: 'Pajak bunga deposito',
                    );
                }

                $account->update([
                    'total_interest_paid' => $netInterest,
                    'total_tax_paid' => $taxAmount,
                ]);
            }

            return $account;
        });
    }
}
