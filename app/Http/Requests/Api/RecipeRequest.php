<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RecipeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'menu_item_id' => [$required, 'uuid', 'exists:menu_items,id'],
            'ingredient_id' => [$required, 'uuid', 'exists:ingredients,id'],
            'quantity' => [$required, 'numeric', 'min:0.001'],
        ];
    }
}
