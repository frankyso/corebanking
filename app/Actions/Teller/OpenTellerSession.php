<?php

namespace App\Actions\Teller;

use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\DTOs\Teller\OpenTellerSessionData;
use App\Enums\TellerSessionStatus;
use App\Enums\VaultTransactionType;
use App\Exceptions\Teller\TellerSessionAlreadyOpenException;
use App\Models\TellerSession;
use Illuminate\Support\Facades\DB;

class OpenTellerSession
{
    use CreatesTellerTransactions;

    public function execute(OpenTellerSessionData $dto): TellerSession
    {
        $existingSession = TellerSession::query()
            ->forUser($dto->teller->id)
            ->open()
            ->first();

        throw_if($existingSession, TellerSessionAlreadyOpenException::alreadyOpen($dto->teller));

        return DB::transaction(function () use ($dto): TellerSession {
            $this->createVaultTransaction(
                vault: $dto->vault,
                type: VaultTransactionType::TellerRequest,
                amount: $dto->openingBalance,
                description: "Kas awal teller {$dto->teller->name}",
                performer: $dto->teller,
            );

            return TellerSession::create([
                'user_id' => $dto->teller->id,
                'branch_id' => $dto->teller->branch_id ?? $dto->vault->branch_id,
                'vault_id' => $dto->vault->id,
                'status' => TellerSessionStatus::Open,
                'opening_balance' => $dto->openingBalance,
                'current_balance' => $dto->openingBalance,
                'total_cash_in' => 0,
                'total_cash_out' => 0,
                'transaction_count' => 0,
                'opened_at' => now(),
            ]);
        });
    }
}
