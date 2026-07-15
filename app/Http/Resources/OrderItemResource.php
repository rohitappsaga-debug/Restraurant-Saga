<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'kot_id' => $this->kot_id,
            'menu_item_id' => $this->menu_item_id,
            'menu_item' => new MenuItemResource($this->whenLoaded('menuItem')),
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'modifiers' => $this->modifiers ?? [],
            'served_at' => $this->served_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
        ];
    }
}
