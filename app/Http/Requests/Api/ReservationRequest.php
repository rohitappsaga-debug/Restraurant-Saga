<?php

namespace App\Http\Requests\Api;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'table_number' => [$required, 'integer', 'exists:tables,number'],
            'customer_name' => [$required, 'string', 'max:150'],
            'customer_phone' => [$required, 'string', 'max:30'],
            'date' => [$required, 'date'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', Rule::enum(ReservationStatus::class)],
        ];
    }
}
