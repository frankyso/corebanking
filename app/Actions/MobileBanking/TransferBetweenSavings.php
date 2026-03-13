<?php

namespace App\Actions\MobileBanking;

use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\WithdrawFromSavings;
use App\DTOs\MobileBanking\TransferData;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Exceptions\MobileBanking\TransferException;
use App\Models\TransferTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferBetweenSavings
{
    public function __construct(
        private WithdrawFromSavings $withdrawFromSavings,
        private DepositToSavings $depositToSavings,
    ) {}

    public function execute(TransferData $dto): TransferTransaction
    {
        if ($dto->sourceAccount->id === $dto->destinationAccount->id) {
            throw TransferException::sameAccount();
        }

        $transferType = $dto->sourceAccount->customer_id === $dto->destinationAccount->customer_id
            ? TransferType::OwnAccount
            : TransferType::InternalTransfer;

        // Get or create system user for performing the transaction
        $systemUser = User::firstOrCreate(
            ['employee_id' => 'MOBILE_SYSTEM'],
            [
                'name' => 'Mobile Banking System',
                'email' => 'mobile-system@corebanking.test',
                'password' => bcrypt(bin2hex(random_bytes(32))),
                'is_active' => true,
            ],
        );

        return DB::transaction(function () use ($dto, $transferType, $systemUser): TransferTransaction {
            $description = $dto->description ?? 'Transfer via Mobile Banking';

            $this->withdrawFromSavings->execute(
                account: $dto->sourceAccount,
                amount: $dto->amount,
                performer: $systemUser,
                description: $description,
            );

            $this->depositToSavings->execute(
                account: $dto->destinationAccount,
                amount: $dto->amount,
                performer: $systemUser,
                description: $description,
            );

            return TransferTransaction::create([
                'reference_number' => $this->generateReferenceNumber(),
                'source_savings_account_id' => $dto->sourceAccount->id,
                'destination_savings_account_id' => $dto->destinationAccount->id,
                'amount' => $dto->amount,
                'fee' => 0,
                'description' => $description,
                'transfer_type' => $transferType,
                'status' => TransferStatus::Completed,
                'performed_by' => $dto->performer->id,
                'performed_at' => now(),
            ]);
        });
    }

    private function generateReferenceNumber(): string
    {
        return 'TRF'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
