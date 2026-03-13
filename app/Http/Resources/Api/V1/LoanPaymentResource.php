<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanPayment */
class LoanPaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference_number' => $this->reference_number,
            'amount' => (float) $this->amount,
            'principal_portion' => (float) $this->principal_portion,
            'interest_portion' => (float) $this->interest_portion,
            'penalty_portion' => (float) $this->penalty_portion,
            'payment_date' => $this->payment_date,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
