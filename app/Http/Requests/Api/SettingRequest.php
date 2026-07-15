<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'restaurant_name' => 'sometimes|required|string|max:255',
            'restaurant_address' => 'nullable|string|max:500',
            'gst_no' => 'nullable|string|max:50',
            'currency' => 'sometimes|string|max:5',
            'tax_enabled' => 'sometimes|boolean',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount_presets' => 'sometimes|array',
            'discount_presets.*' => 'numeric|min:0|max:100',
            'enabled_payment_methods' => 'sometimes|array',
            'enabled_payment_methods.*' => 'string|in:cash,card,upi',
            'business_hours' => 'sometimes|array',
            'business_hours.open' => 'nullable|string|max:20',
            'business_hours.close' => 'nullable|string|max:20',
            'printer_config' => 'sometimes|array',
            'printer_config.enabled' => 'nullable|boolean',
            'printer_config.printerName' => 'nullable|string|max:100',
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.order' => 'nullable|boolean',
            'notification_preferences.payment' => 'nullable|boolean',
            'notification_preferences.low_stock' => 'nullable|boolean',
            'receipt_footer' => 'nullable|string|max:500',
            'reservation_grace_period' => 'nullable|integer|min:0',
        ];
    }
}
