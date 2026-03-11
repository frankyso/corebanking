<?php

namespace App\Actions\Teller;

use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\Enums\TellerSessionStatus;
use App\Enums\VaultTransactionType;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\TellerSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CloseTellerSession
{
    use CreatesTellerTransactions;

    public function execute(TellerSession $session, User $performer, ?string $notes = null): TellerSession
    {
        throw_unless($session->isOpen(), TellerSessionClosedException::alreadyClosed($session));

        return DB::transaction(function () use ($session, $performer, $notes): TellerSession {
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
}
