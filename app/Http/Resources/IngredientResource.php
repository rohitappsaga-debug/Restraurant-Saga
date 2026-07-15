<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => (float) $this->stock,
            'min_level' => (float) $this->min_level,
            'is_low' => (float) $this->stock <= (float) $this->min_level,
        ];
    }
}
