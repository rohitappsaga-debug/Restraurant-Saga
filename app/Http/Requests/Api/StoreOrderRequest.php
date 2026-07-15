<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'table_ids' => 'required|array|min:1',
            'table_ids.*' => 'required|uuid|exists:tables,id',
            'items' => 'nullable|array',
            'items.*.menu_item_id' => 'required_with:items|uuid|exists:menu_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:99',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.modifier_ids' => 'nullable|array',
            'items.*.modifier_ids.*' => 'uuid|exists:menu_item_modifiers,id',
        ];
    }
}
