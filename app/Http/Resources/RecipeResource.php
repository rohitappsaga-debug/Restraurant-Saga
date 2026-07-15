<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'ingredient_id' => $this->ingredient_id,
            'quantity' => (float) $this->quantity,
            'menu_item' => new MenuItemResource($this->whenLoaded('menuItem')),
            'ingredient' => new IngredientResource($this->whenLoaded('ingredient')),
        ];
    }
}
