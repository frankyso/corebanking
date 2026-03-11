<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function place(
        DepositProduct $product,
        int $customerId,
        int $branchId,
        float $principalAmount,
        int $tenorMonths,
        InterestPaymentMethod $interestPaymentMethod,
        RolloverType $rolloverType,
        ?int $savingsAccountId,
        User $performer,
        ?Carbon $placementDate = null,
    ): DepositAccount {
        if ($principalAmount < (float) $product->min_amount) {
            throw new \InvalidArgumentException(
                'Nominal minimal deposito Rp '.number_format((float) $product->min_amount, 0, ',', '.')
            );
        }

        if ($product->max_amount && $principalAmount > (float) $product->max_amount) {
            throw new \InvalidArgumentException(
                'Nominal maksimal deposito Rp '.number_format((float) $product->max_amount, 0, ',', '.')
            );
        }

        $rate = $product->getRateForTenorAndAmount($tenorMonths, $principalAmount);
        if (! $rate) {
            throw new \InvalidArgumentException("Tidak ada suku bunga untuk tenor {$tenorMonths} bulan dengan nominal tersebut");
        }

        return DB::transaction(function () use ($product, $customerId, $branchId, $principalAmount, $tenorMonths, $interestPaymentMethod, $rolloverType, $savingsAccountId, $performer, $placementDate, $rate) {
            $branchCode = $performer->branch?->code ?? '001';
            $accountNumber = $this->sequenceService->generateAccountNumber($product->code, $branchCode);
            $placement = $placementDate ?? now();
            $maturity = $placement->copy()->addMonths($tenorMonths);

            $account = DepositAccount::create([
                'account_number' => $accountNumber,
                'customer_id' => $customerId,
                'deposit_product_id' => $product->id,
                'branch_id' => $branchId,
                'status' => DepositStatus::Active,
                'principal_amount' => $principalAmount,
                'interest_rate' => $rate->interest_rate,
                'tenor_months' => $tenorMonths,
                'interest_payment_method' => $interestPaymentMethod,
                'rollover_type' => $rolloverType,
                'placement_date' => $placement,
                'maturity_date' => $maturity,
                'accrued_interest' => 0,
                'total_interest_paid' => 0,
                'total_tax_paid' => 0,
                'is_pledged' => false,
                'savings_account_id' => $savingsAccountId,
                'created_by' => $performer->id,
            ]);

            $this->createTransaction(
                account: $account,
                type: 'placement',
                amount: $principalAmount,
                performer: $performer,
                description: 'Penempatan deposito',
            );

            if ($interestPaymentMethod === InterestPaymentMethod::Upfront) {
                $totalInterest = $this->calculateTotalInterest($principalAmount, (float) $rate->interest_rate, $tenorMonths);
                $taxAmount = $this->calculateTax($product, $totalInterest);
                $netInterest = bcsub((string) $totalInterest, (string) $taxAmount, 2);

                $this->createTransaction(
                    account: $account,
                    type: 'interest_payment',
                    amount: (float) $netInterest,
                    performer: $performer,
                    description: 'Pembayaran bunga di muka',
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
                    'total_interest_paid' => $netInterest,
                    'total_tax_paid' => $taxAmount,
                ]);
            }

            return $account;
        });
    }

    public function processMaturity(DepositAccount $account, User $performer): DepositAccount
    {
        if ($account->status !== DepositStatus::Active) {
            throw new \InvalidArgumentException('Deposito tidak dalam status aktif');
        }

        if (! $account->isMatured()) {
            throw new \InvalidArgumentException('Deposito belum jatuh tempo');
        }

        return DB::transaction(function () use ($account, $performer) {
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
                    'total_interest_paid' => bcadd($account->total_interest_paid, (string) $netInterest, 2),
                    'total_tax_paid' => bcadd($account->total_tax_paid, (string) $taxAmount, 2),
                    'last_interest_paid_at' => now(),
                ]);
            }

            if ($account->rollover_type === RolloverType::None) {
                $account->update(['status' => DepositStatus::Matured]);

                return $account->fresh();
            }

            return $this->rollover($account, $performer);
        });
    }

    public function rollover(DepositAccount $account, User $performer): DepositAccount
    {
        return DB::transaction(function () use ($account, $performer) {
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
                $newPrincipal = bcadd((string) $newPrincipal, $netUnpaid, 2);
            }

            $rate = $product->getRateForTenorAndAmount($account->tenor_months, (float) $newPrincipal);
            $newRate = $rate ? (float) $rate->interest_rate : (float) $account->interest_rate;

            $newPlacementDate = $account->maturity_date;
            $newMaturityDate = $newPlacementDate->copy()->addMonths($account->tenor_months);

            $account->update([
                'status' => DepositStatus::Rolled,
            ]);

            $this->createTransaction(
                account: $account,
                type: 'rollover',
                amount: (float) $newPrincipal,
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

    public function earlyWithdrawal(DepositAccount $account, User $performer): DepositAccount
    {
        if ($account->status !== DepositStatus::Active) {
            throw new \InvalidArgumentException('Deposito tidak dalam status aktif');
        }

        if ($account->is_pledged) {
            throw new \InvalidArgumentException('Deposito sedang dijaminkan, tidak dapat dicairkan');
        }

        return DB::transaction(function () use ($account, $performer) {
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

    public function pledge(DepositAccount $account, string $pledgeReference): void
    {
        if ($account->status !== DepositStatus::Active) {
            throw new \InvalidArgumentException('Deposito tidak dalam status aktif');
        }

        if ($account->is_pledged) {
            throw new \InvalidArgumentException('Deposito sudah dijaminkan');
        }

        $account->update([
            'is_pledged' => true,
            'pledge_reference' => $pledgeReference,
        ]);
    }

    public function unpledge(DepositAccount $account): void
    {
        if (! $account->is_pledged) {
            throw new \InvalidArgumentException('Deposito tidak sedang dijaminkan');
        }

        $account->update([
            'is_pledged' => false,
            'pledge_reference' => null,
        ]);
    }

    public function accrueDaily(DepositAccount $account, Carbon $date): void
    {
        if ($account->status !== DepositStatus::Active) {
            return;
        }

        $daysInYear = $date->isLeapYear() ? 366 : 365;
        $dailyRate = bcdiv((string) $account->interest_rate, (string) ($daysInYear * 100), 10);
        $accruedAmount = bcmul((string) $account->principal_amount, $dailyRate, 2);
        $taxAmount = $this->calculateTax($account->depositProduct, (float) $accruedAmount);

        $account->interestAccruals()->create([
            'accrual_date' => $date,
            'principal' => $account->principal_amount,
            'interest_rate' => $account->interest_rate,
            'accrued_amount' => $accruedAmount,
            'tax_amount' => $taxAmount,
            'is_posted' => false,
        ]);

        $account->update([
            'accrued_interest' => bcadd($account->accrued_interest, $accruedAmount, 2),
        ]);
    }

    public function payMonthlyInterest(DepositAccount $account, User $performer): void
    {
        if ($account->interest_payment_method !== InterestPaymentMethod::Monthly) {
            return;
        }

        if ($account->status !== DepositStatus::Active) {
            return;
        }

        DB::transaction(function () use ($account, $performer) {
            $accruedInterest = (float) $account->accrued_interest;
            if ($accruedInterest <= 0) {
                return;
            }

            $taxAmount = $this->calculateTax($account->depositProduct, $accruedInterest);
            $netInterest = bcsub((string) $accruedInterest, (string) $taxAmount, 2);

            $this->createTransaction(
                account: $account,
                type: 'interest_payment',
                amount: (float) $netInterest,
                performer: $performer,
                description: 'Pembayaran bunga bulanan',
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
                'accrued_interest' => 0,
                'total_interest_paid' => bcadd($account->total_interest_paid, (string) $netInterest, 2),
                'total_tax_paid' => bcadd($account->total_tax_paid, (string) $taxAmount, 2),
                'last_interest_paid_at' => now(),
            ]);

            $account->interestAccruals()
                ->where('is_posted', false)
                ->update(['is_posted' => true, 'posted_at' => now()]);
        });
    }

    public function getSequenceService(): SequenceService
    {
        return $this->sequenceService;
    }

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
