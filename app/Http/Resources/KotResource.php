<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'batch_number' => $this->batch_number,
            'sent_at' => $this->sent_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
