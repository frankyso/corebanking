<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MobileNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MobileNotification */
class MobileNotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var MobileNotification $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'title' => $model->title,
            'body' => $model->body,
            'type' => $model->type->value,
            'data' => $model->data,
            'is_read' => $model->is_read,
            'read_at' => $model->read_at?->toIso8601String(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
