<?php

namespace App\Actions\Teller;

use App\Actions\Loan\MakeLoanPayment;
use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\DTOs\Loan\MakeLoanPaymentData;
use App\DTOs\Teller\TellerTransactionData;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\LoanAccount;
use App\Models\TellerTransaction;
use Illuminate\Support\Facades\DB;

class ProcessTellerLoanPayment
{
    use CreatesTellerTransactions;

    public function __construct(
        private MakeLoanPayment $makeLoanPayment,
    ) {}

    public function execute(TellerTransactionData $dto, LoanAccount $loanAccount): TellerTransaction
    {
        throw_unless($dto->session->isOpen(), TellerSessionClosedException::notOpen($dto->session));

        return DB::transaction(function () use ($dto, $loanAccount): TellerTransaction {
            $payment = $this->makeLoanPayment->execute(new MakeLoanPaymentData(
                account: $loanAccount,
                amount: $dto->amount,
                performer: $dto->performer,
                description: $dto->description,
            ));

            return $this->createTellerTransaction(
                session: $dto->session,
                type: TellerTransactionType::LoanPayment,
                amount: $dto->amount,
                direction: 'in',
                description: $dto->description ?? "Bayar angsuran {$loanAccount->account_number}",
                performer: $dto->performer,
                customerId: $loanAccount->customer_id,
                referenceType: 'loan_payment',
                referenceId: $payment->id,
            );
        });
    }
}
