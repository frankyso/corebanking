<?php

namespace App\Actions\Teller\Concerns;

use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;

trait CreatesTellerTransactions
{
    private function createTellerTransaction(
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

    private function createVaultTransaction(
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

    private function needsAuthorization(float $amount): bool
    {
        $limit = (float) (SystemParameter::getValue('teller', 'authorization_limit', '100000000'));

        return $amount >= $limit;
    }

    private function generateReference(): string
    {
        return 'TLR'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function generateVaultReference(): string
    {
        return 'VLT'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
