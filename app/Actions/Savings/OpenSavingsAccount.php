<?php

namespace App\Actions\Savings;

use App\Actions\Savings\Concerns\CreatesSavingsTransaction;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\SavingsAccount;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;

class OpenSavingsAccount
{
    use CreatesSavingsTransaction;

    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function execute(OpenSavingsAccountData $dto): SavingsAccount
    {
        if ($dto->initialDeposit < (float) $dto->product->min_opening_balance) {
            throw SavingsBalanceLimitException::belowMinimumOpeningBalance($dto->product);
        }

        return DB::transaction(function () use ($dto): SavingsAccount {
            $branchCode = $dto->performer->branch?->code ?? '001';
            $accountNumber = $this->sequenceService->generateAccountNumber($dto->product->code, $branchCode);

            $account = SavingsAccount::create([
                'account_number' => $accountNumber,
                'customer_id' => $dto->customerId,
                'savings_product_id' => $dto->product->id,
                'branch_id' => $dto->branchId,
                'status' => SavingsAccountStatus::Active,
                'balance' => $dto->initialDeposit,
                'hold_amount' => 0,
                'available_balance' => $dto->initialDeposit,
                'accrued_interest' => 0,
                'opened_at' => now(),
                'last_transaction_at' => now(),
                'created_by' => $dto->performer->id,
            ]);

            $this->createTransaction(
                account: $account,
                type: SavingsTransactionType::Opening,
                amount: $dto->initialDeposit,
                performer: $dto->performer,
                description: 'Pembukaan rekening tabungan',
            );

            return $account;
        });
    }
}
