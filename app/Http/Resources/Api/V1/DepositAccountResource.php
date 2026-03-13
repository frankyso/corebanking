<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DepositAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepositAccount */
class DepositAccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var DepositAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product' => [
                'code' => $model->depositProduct?->code,
                'name' => $model->depositProduct?->name,
            ],
            'status' => $model->status->value,
            'principal_amount' => (float) $model->principal_amount,
            'interest_rate' => (float) $model->interest_rate,
            'tenor_months' => $model->tenor_months,
            'interest_payment_method' => $model->interest_payment_method->value,
            'rollover_type' => $model->rollover_type->value,
            'placement_date' => $model->placement_date?->format('Y-m-d'),
            'maturity_date' => $model->maturity_date?->format('Y-m-d'),
            'accrued_interest' => (float) $model->accrued_interest,
        ];
    }
}
