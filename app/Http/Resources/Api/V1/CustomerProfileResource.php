<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Customer $model */
        $model = $this->resource;

        return [
            'cif_number' => $model->cif_number,
            'customer_type' => $model->customer_type->value,
            'status' => $model->status->value,
            'display_name' => $model->display_name,
            'branch' => [
                'id' => $model->branch?->id,
                'name' => $model->branch?->name,
            ],
            'risk_rating' => $model->risk_rating->value,
        ];
    }
}
