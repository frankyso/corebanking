<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DepositTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DepositTransaction */
class DepositTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
