<?php

namespace App\Http\Resources\Api\V1;

use App\Models\IndividualDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IndividualDetail */
class IndividualDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var IndividualDetail $model */
        $model = $this->resource;

        return [
            'full_name' => $model->full_name,
            'birth_place' => $model->birth_place,
            'birth_date' => $model->birth_date?->format('Y-m-d'),
            'gender' => $model->gender?->value,
            'marital_status' => $model->marital_status?->value,
            'occupation' => $model->occupation,
            'nationality' => $model->nationality,
        ];
    }
}
