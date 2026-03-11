<?php

namespace App\Services;

use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SavingsService
{
    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function open(
        SavingsProduct $product,
        int $customerId,
        int $branchId,
        float $initialDeposit,
        User $performer,
    ): SavingsAccount {
        if ($initialDeposit < (float) $product->min_opening_balance) {
            throw new \InvalidArgumentException(
                'Setoran awal minimal Rp '.number_format((float) $product->min_opening_balance, 0, ',', '.')
            );
        }

        return DB::transaction(function () use ($product, $customerId, $branchId, $initialDeposit, $performer) {
            $branchCode = $performer->branch?->code ?? '001';
            $accountNumber = $this->sequenceService->generateAccountNumber($product->code, $branchCode);

            $account = SavingsAccount::create([
                'account_number' => $accountNumber,
                'customer_id' => $customerId,
                'savings_product_id' => $product->id,
                'branch_id' => $branchId,
                'status' => SavingsAccountStatus::Active,
                'balance' => $initialDeposit,
                'hold_amount' => 0,
                'available_balance' => $initialDeposit,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'last_transaction_at' => now(),
                'created_by' => $performer->id,
            ]);

            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Opening,
                amount: $initialDeposit,
                performer: $performer,
                description: 'Pembukaan rekening tabungan',
            );

            return $account;
        });
    }

    public function deposit(
        SavingsAccount $account,
        float $amount,
        User $performer,
        ?string $description = null,
    ): SavingsTransaction {
        $this->validateActiveAccount($account);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah setoran harus lebih dari 0');
        }

        $product = $account->savingsProduct;
        if ($product->max_balance && bcadd($account->balance, (string) $amount, 2) > (float) $product->max_balance) {
            throw new \InvalidArgumentException('Saldo melebihi batas maksimal');
        }

        return DB::transaction(function () use ($account, $amount, $performer, $description) {
            $transaction = $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Deposit,
                amount: $amount,
                performer: $performer,
                description: $description ?? 'Setoran tunai',
            );

            $account->update([
                'balance' => bcadd($account->balance, (string) $amount, 2),
                'last_transaction_at' => now(),
                'dormant_at' => null,
                'status' => SavingsAccountStatus::Active,
            ]);
            $account->recalculateAvailableBalance();

            return $transaction;
        });
    }

    public function withdraw(
        SavingsAccount $account,
        float $amount,
        User $performer,
        ?string $description = null,
    ): SavingsTransaction {
        $this->validateActiveAccount($account);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah penarikan harus lebih dari 0');
        }

        if ($amount > (float) $account->available_balance) {
            throw new \InvalidArgumentException('Saldo tidak mencukupi');
        }

        $product = $account->savingsProduct;
        $remainingBalance = bcsub($account->balance, (string) $amount, 2);
        if ((float) $remainingBalance < (float) $product->min_balance) {
            throw new \InvalidArgumentException(
                'Saldo minimal Rp '.number_format((float) $product->min_balance, 0, ',', '.')
            );
        }

        return DB::transaction(function () use ($account, $amount, $performer, $description) {
            $transaction = $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Withdrawal,
                amount: $amount,
                performer: $performer,
                description: $description ?? 'Penarikan tunai',
            );

            $account->update([
                'balance' => bcsub($account->balance, (string) $amount, 2),
                'last_transaction_at' => now(),
            ]);
            $account->recalculateAvailableBalance();

            return $transaction;
        });
    }

    public function hold(SavingsAccount $account, float $amount, User $performer): void
    {
        $this->validateActiveAccount($account);

        if ($amount > (float) $account->available_balance) {
            throw new \InvalidArgumentException('Saldo tersedia tidak mencukupi untuk pemblokiran');
        }

        DB::transaction(function () use ($account, $amount, $performer) {
            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Hold,
                amount: $amount,
                performer: $performer,
                description: 'Pemblokiran saldo Rp '.number_format($amount, 0, ',', '.'),
            );

            $account->update([
                'hold_amount' => bcadd($account->hold_amount, (string) $amount, 2),
            ]);
            $account->recalculateAvailableBalance();
        });
    }

    public function unhold(SavingsAccount $account, float $amount, User $performer): void
    {
        $this->validateActiveAccount($account);

        if ($amount > (float) $account->hold_amount) {
            throw new \InvalidArgumentException('Jumlah melebihi saldo yang diblokir');
        }

        DB::transaction(function () use ($account, $amount, $performer) {
            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Unhold,
                amount: $amount,
                performer: $performer,
                description: 'Pembukaan blokir Rp '.number_format($amount, 0, ',', '.'),
            );

            $account->update([
                'hold_amount' => bcsub($account->hold_amount, (string) $amount, 2),
            ]);
            $account->recalculateAvailableBalance();
        });
    }

    public function freeze(SavingsAccount $account): void
    {
        $this->validateActiveAccount($account);
        $account->update(['status' => SavingsAccountStatus::Frozen]);
    }

    public function unfreeze(SavingsAccount $account): void
    {
        if ($account->status !== SavingsAccountStatus::Frozen) {
            throw new \InvalidArgumentException('Rekening tidak dalam status dibekukan');
        }
        $account->update(['status' => SavingsAccountStatus::Active]);
    }

    public function close(SavingsAccount $account, User $performer): ?SavingsTransaction
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw new \InvalidArgumentException('Rekening tidak dapat ditutup');
        }

        if ((float) $account->hold_amount > 0) {
            throw new \InvalidArgumentException('Rekening masih memiliki saldo diblokir');
        }

        return DB::transaction(function () use ($account, $performer) {
            $closingFee = (float) $account->savingsProduct->closing_fee;
            $remainingBalance = (float) $account->balance;
            $transaction = null;

            if ($remainingBalance > 0) {
                $transaction = $this->createTransaction(
                    account: $account,
                    type: SavingsTransactionType::Closing,
                    amount: $remainingBalance,
                    performer: $performer,
                    description: 'Penutupan rekening',
                );
            }

            $account->update([
                'balance' => 0,
                'available_balance' => 0,
                'status' => SavingsAccountStatus::Closed,
                'closed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function markDormant(SavingsAccount $account): void
    {
        if ($account->status !== SavingsAccountStatus::Active) {
            return;
        }

        $account->update([
            'status' => SavingsAccountStatus::Dormant,
            'dormant_at' => now(),
        ]);
    }

    protected function createTransaction(
        SavingsAccount $account,
        SavingsTransactionType $type,
        float $amount,
        User $performer,
        ?string $description = null,
    ): SavingsTransaction {
        $balanceBefore = (float) $account->balance;
        $balanceAfter = $type->isCredit()
            ? bcadd((string) $balanceBefore, (string) $amount, 2)
            : bcsub((string) $balanceBefore, (string) $amount, 2);

        $referenceNumber = $this->generateTransactionReference();

        return SavingsTransaction::create([
            'reference_number' => $referenceNumber,
            'savings_account_id' => $account->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'value_date' => now()->toDateString(),
            'performed_by' => $performer->id,
        ]);
    }

    protected function generateTransactionReference(): string
    {
        return 'TRX'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function validateActiveAccount(SavingsAccount $account): void
    {
        if (! in_array($account->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant])) {
            throw new \InvalidArgumentException('Rekening tidak aktif');
        }
    }
}
