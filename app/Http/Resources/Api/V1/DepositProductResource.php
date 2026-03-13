<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DepositProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepositProduct */
class DepositProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'min_amount' => (float) $this->min_amount,
            'max_amount' => $this->max_amount ? (float) $this->max_amount : null,
            'penalty_rate' => (float) $this->penalty_rate,
            'rates' => DepositProductRateResource::collection($this->whenLoaded('rates')),
        ];
    }
}
