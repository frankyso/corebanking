<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SavingsAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavingsAccount */
class SavingsAccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var SavingsAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product' => [
                'code' => $model->savingsProduct?->code,
                'name' => $model->savingsProduct?->name,
            ],
            'branch' => [
                'id' => $model->branch?->id,
                'name' => $model->branch?->name,
            ],
            'status' => $model->status->value,
            'balance' => (float) $model->balance,
            'hold_amount' => (float) $model->hold_amount,
            'available_balance' => (float) $model->available_balance,
            'accrued_interest' => (float) $model->accrued_interest,
            'opened_at' => $model->opened_at?->format('Y-m-d'),
            'last_transaction_at' => $model->last_transaction_at?->format('Y-m-d'),
        ];
    }
}
