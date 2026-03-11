<?php

namespace App\Actions\Teller;

use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\DB;

class ReturnTellerCash
{
    use CreatesTellerTransactions;

    public function execute(TellerSession $session, Vault $vault, float $amount, User $performer): TellerTransaction
    {
        throw_unless($session->isOpen(), TellerSessionClosedException::notOpen($session));

        throw_if(
            $amount > (float) $session->current_balance,
            InsufficientTellerCashException::insufficientBalance($session, $amount),
        );

        return DB::transaction(function () use ($session, $vault, $amount, $performer): TellerTransaction {
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
}
