<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'restaurant_name' => $this->restaurant_name,
            'restaurant_address' => $this->restaurant_address,
            'gst_no' => $this->gst_no,
            'currency' => $this->currency,
            'tax_enabled' => (bool) $this->tax_enabled,
            'tax_rate' => (float) $this->tax_rate,
            'discount_presets' => $this->discount_presets ?? [],
            'enabled_payment_methods' => $this->enabled_payment_methods ?? ['cash', 'card', 'upi'],
            'business_hours' => $this->business_hours ?? ['open' => '09:00 AM', 'close' => '10:00 PM'],
            'printer_config' => $this->printer_config ?? ['enabled' => false, 'printerName' => ''],
            'notification_preferences' => $this->notification_preferences ?? ['order' => true, 'payment' => true, 'low_stock' => false],
            'receipt_footer' => $this->receipt_footer,
            'reservation_grace_period' => $this->reservation_grace_period,
        ];
    }
}
