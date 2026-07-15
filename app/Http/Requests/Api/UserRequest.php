<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $required = $isCreate ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:150'],
            'email' => [
                $required, 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => [$required, Rule::enum(UserRole::class)],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
