<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoanAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanAccount */
class LoanAccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var LoanAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product' => [
                'code' => $model->loanProduct?->code,
                'name' => $model->loanProduct?->name,
                'interest_type' => $model->loanProduct?->interest_type->value,
            ],
            'status' => $model->status->value,
            'principal_amount' => (float) $model->principal_amount,
            'outstanding_principal' => (float) $model->outstanding_principal,
            'interest_rate' => (float) $model->interest_rate,
            'tenor_months' => $model->tenor_months,
            'dpd' => $model->dpd,
            'collectibility' => $model->collectibility?->value,
            'disbursement_date' => $model->disbursement_date?->format('Y-m-d'),
            'maturity_date' => $model->maturity_date?->format('Y-m-d'),
            'last_payment_date' => $model->last_payment_date?->format('Y-m-d'),
            'total_principal_paid' => (float) $model->total_principal_paid,
            'total_interest_paid' => (float) $model->total_interest_paid,
        ];
    }
}
