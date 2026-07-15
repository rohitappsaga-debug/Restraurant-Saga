<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('categoryInfo')),
            'available' => $this->available,
            'availability_reason' => $this->availability_reason,
            'is_veg' => $this->is_veg,
            'preparation_time' => $this->preparation_time,
            'thumbnail_url' => $this->thumbnail_url,
            'modifiers' => MenuItemModifierResource::collection($this->whenLoaded('modifiers')),
        ];
    }
}
