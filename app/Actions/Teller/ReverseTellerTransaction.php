<?php

namespace App\Actions\Teller;

use App\Actions\Teller\Concerns\CreatesTellerTransactions;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\TellerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReverseTellerTransaction
{
    use CreatesTellerTransactions;

    public function execute(TellerTransaction $transaction, User $performer, string $reason): TellerTransaction
    {
        throw_if($transaction->is_reversed, new \InvalidArgumentException('Transaksi sudah pernah dibatalkan'));

        $session = $transaction->tellerSession;
        throw_unless($session->isOpen(), TellerSessionClosedException::notOpen($session));

        return DB::transaction(function () use ($transaction, $session, $performer, $reason): TellerTransaction {
            $reverseDirection = $transaction->isCashIn() ? 'out' : 'in';

            $reversalTx = $this->createTellerTransaction(
                session: $session,
                type: $transaction->transaction_type,
                amount: (float) $transaction->amount,
                direction: $reverseDirection,
                description: "Reversal: {$reason}",
                performer: $performer,
                customerId: $transaction->customer_id,
                referenceType: $transaction->reference_type,
                referenceId: $transaction->reference_id,
            );

            $transaction->update([
                'is_reversed' => true,
                'reversed_by_id' => $reversalTx->id,
            ]);

            return $reversalTx;
        });
    }
}
