<?php

namespace App\Actions\Teller;

use App\Actions\Savings\DepositToSavings;
use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\SavingsAccount;
use App\Models\TellerTransaction;
use Illuminate\Support\Facades\DB;

class ProcessTellerDeposit
{
    use CreatesTellerTransactions;

    public function __construct(
        private DepositToSavings $depositToSavings,
    ) {}

    public function execute(TellerTransactionData $dto, SavingsAccount $account): TellerTransaction
    {
        throw_unless($dto->session->isOpen(), TellerSessionClosedException::notOpen($dto->session));

        return DB::transaction(function () use ($dto, $account): TellerTransaction {
            $this->depositToSavings->execute($account, $dto->amount, $dto->performer, $dto->description);

            return $this->createTellerTransaction(
                session: $dto->session,
                type: TellerTransactionType::SavingsDeposit,
                amount: $dto->amount,
                direction: 'in',
                description: $dto->description ?? "Setor tabungan {$account->account_number}",
                performer: $dto->performer,
                customerId: $account->customer_id,
                referenceType: 'savings_account',
                referenceId: $account->id,
            );
        });
    }
}
