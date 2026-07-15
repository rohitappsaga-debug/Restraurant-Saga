<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'ingredient_id' => $this->ingredient_id,
            'ingredient' => new IngredientResource($this->whenLoaded('ingredient')),
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'line_total' => round((float) $this->quantity * (float) $this->unit_cost, 2),
        ];
    }
}
