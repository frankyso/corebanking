<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanSchedule */
class LoanScheduleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var LoanSchedule $model */
        $model = $this->resource;

        return [
            'installment_number' => $model->installment_number,
            'due_date' => $model->due_date?->format('Y-m-d'),
            'principal_amount' => (float) $model->principal_amount,
            'interest_amount' => (float) $model->interest_amount,
            'installment_amount' => (float) bcadd((string) $model->principal_amount, (string) $model->interest_amount, 2),
            'principal_paid' => (float) $model->principal_paid,
            'interest_paid' => (float) $model->interest_paid,
            'outstanding_balance' => (float) $model->outstanding_balance,
            'is_paid' => $model->is_paid,
            'paid_date' => $model->paid_date?->format('Y-m-d'),
        ];
    }
}
