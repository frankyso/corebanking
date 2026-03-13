<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Holiday */
class HolidayResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Holiday $model */
        $model = $this->resource;

        return [
            'date' => $model->date?->format('Y-m-d'),
            'name' => $model->name,
            'type' => $model->type,
        ];
    }
}
