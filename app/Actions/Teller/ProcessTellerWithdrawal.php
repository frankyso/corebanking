<?php

namespace App\Actions\Teller;

use App\Actions\Savings\WithdrawFromSavings;
use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\SavingsAccount;
use App\Models\TellerTransaction;
use Illuminate\Support\Facades\DB;

class ProcessTellerWithdrawal
{
    use CreatesTellerTransactions;

    public function __construct(
        private WithdrawFromSavings $withdrawFromSavings,
    ) {}

    public function execute(TellerTransactionData $dto, SavingsAccount $account): TellerTransaction
    {
        throw_unless($dto->session->isOpen(), TellerSessionClosedException::notOpen($dto->session));

        throw_if(
            $dto->amount > (float) $dto->session->current_balance,
            InsufficientTellerCashException::insufficientBalance($dto->session, $dto->amount),
        );

        return DB::transaction(function () use ($dto, $account): TellerTransaction {
            $this->withdrawFromSavings->execute($account, $dto->amount, $dto->performer, $dto->description);

            return $this->createTellerTransaction(
                session: $dto->session,
                type: TellerTransactionType::SavingsWithdrawal,
                amount: $dto->amount,
                direction: 'out',
                description: $dto->description ?? "Tarik tabungan {$account->account_number}",
                performer: $dto->performer,
                customerId: $account->customer_id,
                referenceType: 'savings_account',
                referenceId: $account->id,
            );
        });
    }
}
