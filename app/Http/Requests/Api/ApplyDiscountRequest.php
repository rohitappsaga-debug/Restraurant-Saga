<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
        ];
    }
}
