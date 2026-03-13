<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DepositProductRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepositProductRate */
class DepositProductRateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'tenor_months' => $this->tenor_months,
            'min_amount' => (float) $this->min_amount,
            'max_amount' => $this->max_amount ? (float) $this->max_amount : null,
            'interest_rate' => (float) $this->interest_rate,
        ];
    }
}
