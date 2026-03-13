<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SavingsTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavingsTransaction */
class SavingsTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var SavingsTransaction $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'transaction_type' => $model->transaction_type->value,
            'transaction_type_label' => $model->transaction_type->getLabel(),
            'amount' => (float) $model->amount,
            'balance_after' => (float) $model->balance_after,
            'description' => $model->description,
            'reference_number' => $model->reference_number,
            'is_credit' => $model->transaction_type->isCredit(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
