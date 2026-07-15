<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IngredientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:150'],
            'unit' => [$required, 'string', 'max:30'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'min_level' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
