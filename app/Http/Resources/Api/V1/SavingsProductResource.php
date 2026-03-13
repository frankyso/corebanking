<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SavingsProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavingsProduct */
class SavingsProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var SavingsProduct $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'code' => $model->code,
            'name' => $model->name,
            'description' => $model->description,
            'interest_rate' => (float) $model->interest_rate,
            'min_opening_balance' => (float) $model->min_opening_balance,
            'min_balance' => (float) $model->min_balance,
            'admin_fee' => (float) $model->admin_fee,
        ];
    }
}
