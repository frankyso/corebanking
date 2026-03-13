<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TransferTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransferTransaction */
class TransferTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var TransferTransaction $model */
        $model = $this->resource;

        return [
            'reference_number' => $model->reference_number,
            'source_account' => $model->sourceAccount?->account_number,
            'destination_account' => $model->destinationAccount?->account_number,
            'amount' => (float) $model->amount,
            'fee' => (float) $model->fee,
            'description' => $model->description,
            'transfer_type' => $model->transfer_type->value,
            'status' => $model->status->value,
            'performed_at' => $model->performed_at?->toIso8601String(),
        ];
    }
}
