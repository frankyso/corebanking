<?php

namespace App\Actions\Teller;

use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\DB;

class RequestTellerCash
{
    use CreatesTellerTransactions;

    public function execute(TellerSession $session, Vault $vault, float $amount, User $performer): TellerTransaction
    {
        throw_unless($session->isOpen(), TellerSessionClosedException::notOpen($session));

        return DB::transaction(function () use ($session, $vault, $amount, $performer): TellerTransaction {
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
}
