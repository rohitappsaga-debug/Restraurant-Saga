<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\OrderStatus;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }
}