<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SavingsAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavingsAccount */
class SavingsAccountSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var SavingsAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product_name' => $model->savingsProduct?->name,
            'status' => $model->status->value,
            'balance' => (float) $model->balance,
            'available_balance' => (float) $model->available_balance,
        ];
    }
}
