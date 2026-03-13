<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoanAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanAccount */
class LoanAccountSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var LoanAccount $model */
        $model = $this->resource;

        return [
            'account_number' => $model->account_number,
            'product_name' => $model->loanProduct?->name,
            'status' => $model->status->value,
            'outstanding_principal' => (float) $model->outstanding_principal,
            'interest_rate' => (float) $model->interest_rate,
            'dpd' => $model->dpd,
            'collectibility' => $model->collectibility?->value,
        ];
    }
}
