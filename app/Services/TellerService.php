<?php

namespace App\Services;

use App\Enums\TellerSessionStatus;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Models\LoanAccount;
use App\Models\SavingsAccount;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Support\Facades\DB;

class TellerService
{
    public function __construct(
        private SavingsService $savingsService,
        private LoanService $loanService,
    ) {}

    public function openSession(User $teller, Vault $vault, float $openingBalance): TellerSession
    {
        $existingSession = TellerSession::query()
            ->forUser($teller->id)
            ->open()
            ->first();

        if ($existingSession) {
            throw new \InvalidArgumentException('Teller sudah memiliki sesi aktif');
        }

        return DB::transaction(function () use ($teller, $vault, $openingBalance) {
            $this->createVaultTransaction(
                vault: $vault,
                type: VaultTransactionType::TellerRequest,
                amount: $openingBalance,
                description: "Kas awal teller {$teller->name}",
                performer: $teller,
            );

            return TellerSession::create([
                'user_id' => $teller->id,
                'branch_id' => $teller->branch_id ?? $vault->branch_id,
                'vault_id' => $vault->id,
                'status' => TellerSessionStatus::Open,
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'total_cash_in' => 0,
                'total_cash_out' => 0,
                'transaction_count' => 0,
                'opened_at' => now(),
            ]);
        });
    }

    public function closeSession(TellerSession $session, User $performer, ?string $notes = null): TellerSession
    {
        if (! $session->isOpen()) {
            throw new \InvalidArgumentException('Sesi sudah ditutup');
        }

        return DB::transaction(function () use ($session, $performer, $notes) {
            $currentBalance = (float) $session->current_balance;

            if ($currentBalance > 0) {
                $this->createVaultTransaction(
                    vault: $session->vault,
                    type: VaultTransactionType::TellerReturn,
                    amount: $currentBalance,
                    description: "Pengembalian kas teller {$session->user->name}",
                    performer: $performer,
                );
            }

            $session->update([
                'status' => TellerSessionStatus::Closed,
                'closing_balance' => $currentBalance,
                'closed_at' => now(),
                'closing_notes' => $notes,
            ]);

            return $session->fresh();
        });
    }

    public function processDeposit(
        TellerSession $session,
        SavingsAccount $account,
        float $amount,
        User $performer,
        ?string $description = null,
    ): TellerTransaction {
        $this->validateOpenSession($session);

        return DB::transaction(function () use ($session, $account, $amount, $performer, $description) {
            $this->savingsService->deposit($account, $amount, $performer, $description);

            return $this->createTellerTransaction(
                session: $session,
                type: TellerTransactionType::SavingsDeposit,
                amount: $amount,
                direction: 'in',
                description: $description ?? "Setor tabungan {$account->account_number}",
                customerId: $account->customer_id,
                referenceType: 'savings_account',
                referenceId: $account->id,
                performer: $performer,
            );
        });
    }

    public function processWithdrawal(
        TellerSession $session,
        SavingsAccount $account,
        float $amount,
        User $performer,
        ?string $description = null,
    ): TellerTransaction {
        $this->validateOpenSession($session);

        if ($amount > (float) $session->current_balance) {
            throw new \InvalidArgumentException('Saldo kas teller tidak mencukupi');
        }

        return DB::transaction(function () use ($session, $account, $amount, $performer, $description) {
            $this->savingsService->withdraw($account, $amount, $performer, $description);

            return $this->createTellerTransaction(
                session: $session,
                type: TellerTransactionType::SavingsWithdrawal,
                amount: $amount,
                direction: 'out',
                description: $description ?? "Tarik tabungan {$account->account_number}",
                customerId: $account->customer_id,
                referenceType: 'savings_account',
                referenceId: $account->id,
                performer: $performer,
            );
        });
    }

    public function processLoanPayment(
        TellerSession $session,
        LoanAccount $loanAccount,
        float $amount,
        User $performer,
        ?string $description = null,
    ): TellerTransaction {
        $this->validateOpenSession($session);

        return DB::transaction(function () use ($session, $loanAccount, $amount, $performer, $description) {
            $payment = $this->loanService->makePayment($loanAccount, $amount, $performer, $description);

            return $this->createTellerTransaction(
                session: $session,
                type: TellerTransactionType::LoanPayment,
                amount: $amount,
                direction: 'in',
                description: $description ?? "Bayar angsuran {$loanAccount->account_number}",
                customerId: $loanAccount->customer_id,
                referenceType: 'loan_payment',
                referenceId: $payment->id,
                performer: $performer,
            );
        });
    }

    public function reverseTransaction(TellerTransaction $transaction, User $performer, string $reason): TellerTransaction
    {
        if ($transaction->is_reversed) {
            throw new \InvalidArgumentException('Transaksi sudah pernah dibatalkan');
        }

        $session = $transaction->tellerSession;
        $this->validateOpenSession($session);

        return DB::transaction(function () use ($transaction, $session, $performer, $reason) {
            $reverseDirection = $transaction->isCashIn() ? 'out' : 'in';

            $reversalTx = $this->createTellerTransaction(
                session: $session,
                type: $transaction->transaction_type,
                amount: (float) $transaction->amount,
                direction: $reverseDirection,
                description: "Reversal: {$reason}",
                customerId: $transaction->customer_id,
                referenceType: $transaction->reference_type,
                referenceId: $transaction->reference_id,
                performer: $performer,
            );

            $transaction->update([
                'is_reversed' => true,
                'reversed_by_id' => $reversalTx->id,
            ]);

            return $reversalTx;
        });
    }

    public function requestCash(TellerSession $session, Vault $vault, float $amount, User $performer): TellerTransaction
    {
        $this->validateOpenSession($session);

        return DB::transaction(function () use ($session, $vault, $amount, $performer) {
            $this->createVaultTransaction(
                vault: $vault,
                type: VaultTransactionType::TellerRequest,
                amount: $amount,
                description: "Permintaan kas teller {$session->user->name}",
                performer: $performer,
            );

            return $this->createTellerTransaction(
                session: $session,
                type: TellerTransactionType::CashRequest,
                amount: $amount,
                direction: 'in',
                description: "Permintaan kas dari vault {$vault->code}",
                performer: $performer,
            );
        });
    }

    public function returnCash(TellerSession $session, Vault $vault, float $amount, User $performer): TellerTransaction
    {
        $this->validateOpenSession($session);

        if ($amount > (float) $session->current_balance) {
            throw new \InvalidArgumentException('Saldo kas teller tidak mencukupi');
        }

        return DB::transaction(function () use ($session, $vault, $amount, $performer) {
            $this->createVaultTransaction(
                vault: $vault,
                type: VaultTransactionType::TellerReturn,
                amount: $amount,
                description: "Pengembalian kas teller {$session->user->name}",
                performer: $performer,
            );

            return $this->createTellerTransaction(
                session: $session,
                type: TellerTransactionType::CashReturn,
                amount: $amount,
                direction: 'out',
                description: "Pengembalian kas ke vault {$vault->code}",
                performer: $performer,
            );
        });
    }

    public function needsAuthorization(float $amount): bool
    {
        $limit = (float) (SystemParameter::getValue('teller', 'authorization_limit', '100000000'));

        return $amount >= $limit;
    }

    public function getActiveSession(User $teller): ?TellerSession
    {
        return TellerSession::query()
            ->forUser($teller->id)
            ->open()
            ->first();
    }

    protected function createTellerTransaction(
        TellerSession $session,
        TellerTransactionType $type,
        float $amount,
        string $direction,
        string $description,
        User $performer,
        ?int $customerId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): TellerTransaction {
        $balanceBefore = (float) $session->current_balance;
        $balanceAfter = $direction === 'in'
            ? bcadd((string) $balanceBefore, (string) $amount, 2)
            : bcsub((string) $balanceBefore, (string) $amount, 2);

        $needsAuth = $this->needsAuthorization($amount);

        $transaction = TellerTransaction::create([
            'reference_number' => $this->generateReference(),
            'teller_session_id' => $session->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'teller_balance_before' => $balanceBefore,
            'teller_balance_after' => $balanceAfter,
            'direction' => $direction,
            'description' => $description,
            'customer_id' => $customerId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'needs_authorization' => $needsAuth,
            'performed_by' => $performer->id,
        ]);

        $updateData = [
            'current_balance' => $balanceAfter,
            'transaction_count' => $session->transaction_count + 1,
        ];

        if ($direction === 'in') {
            $updateData['total_cash_in'] = bcadd((string) $session->total_cash_in, (string) $amount, 2);
        } else {
            $updateData['total_cash_out'] = bcadd((string) $session->total_cash_out, (string) $amount, 2);
        }

        $session->update($updateData);

        return $transaction;
    }

    protected function createVaultTransaction(
        Vault $vault,
        VaultTransactionType $type,
        float $amount,
        string $description,
        User $performer,
    ): VaultTransaction {
        $balanceBefore = (float) $vault->balance;

        $isDebit = in_array($type, [VaultTransactionType::CashIn, VaultTransactionType::TellerReturn, VaultTransactionType::InitialCash]);
        $balanceAfter = $isDebit
            ? bcadd((string) $balanceBefore, (string) $amount, 2)
            : bcsub((string) $balanceBefore, (string) $amount, 2);

        $transaction = VaultTransaction::create([
            'reference_number' => $this->generateVaultReference(),
            'vault_id' => $vault->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'performed_by' => $performer->id,
        ]);

        $vault->update(['balance' => $balanceAfter]);

        return $transaction;
    }

    protected function validateOpenSession(TellerSession $session): void
    {
        if (! $session->isOpen()) {
            throw new \InvalidArgumentException('Sesi teller tidak aktif');
        }
    }

    protected function generateReference(): string
    {
        return 'TLR'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function generateVaultReference(): string
    {
        return 'VLT'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
