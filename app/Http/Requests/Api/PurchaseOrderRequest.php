<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'supplier_id' => [$required, 'uuid', 'exists:suppliers,id'],
            'status' => ['sometimes', 'string', 'max:30'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
