<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'capacity' => $this->capacity,
            'status' => $this->status?->value,
            'group_id' => $this->group_id,
            'is_primary' => (bool) $this->is_primary,
            'current_order_id' => $this->current_order_id,
            'has_ready_items' => $this->whenHas('has_ready_items', fn () => (bool) $this->has_ready_items),
            'current_order' => new OrderResource($this->whenLoaded('currentOrder')),
        ];
    }
}
