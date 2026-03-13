<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TransferFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransferFavorite */
class TransferFavoriteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var TransferFavorite $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'account_number' => $model->savingsAccount?->account_number,
            'account_name' => $model->savingsAccount?->customer?->display_name,
            'alias' => $model->alias,
        ];
    }
}
