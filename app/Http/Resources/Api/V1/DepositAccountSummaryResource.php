<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DepositAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepositAccount */
class DepositAccountSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var DepositAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product_name' => $model->depositProduct?->name,
            'status' => $model->status->value,
            'principal_amount' => (float) $model->principal_amount,
            'interest_rate' => (float) $model->interest_rate,
            'maturity_date' => $model->maturity_date?->format('Y-m-d'),
        ];
    }
}
