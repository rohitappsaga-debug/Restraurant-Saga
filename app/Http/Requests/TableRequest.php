<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\TableStatus;

class TableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => 'required|integer|unique:tables,number,' . $this->route('table'),
            'capacity' => 'required|integer|min:1',
            'status' => ['required', Rule::enum(TableStatus::class)],
            'group_id' => 'nullable|string',
            'is_primary' => 'boolean',
        ];
    }
}
