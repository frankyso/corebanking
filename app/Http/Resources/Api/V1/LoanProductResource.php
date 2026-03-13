<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanProduct */
class LoanProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var LoanProduct $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'code' => $model->code,
            'name' => $model->name,
            'description' => $model->description,
            'loan_type' => $model->loan_type->value,
            'interest_type' => $model->interest_type->value,
            'interest_rate' => (float) $model->interest_rate,
            'min_amount' => (float) $model->min_amount,
            'max_amount' => (float) $model->max_amount,
            'min_tenor_months' => $model->min_tenor_months,
            'max_tenor_months' => $model->max_tenor_months,
        ];
    }
}
