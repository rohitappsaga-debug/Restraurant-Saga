<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SettingController extends ApiController
{
    /** Readable by every role — the waiter/kitchen UI needs currency, tax, payment methods, presets. */
    public function show(): JsonResponse
    {
        return $this->respond(new SettingResource($this->settings()));
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $settings = $this->settings();
        $settings->update($request->validated());

        Cache::forget('app_settings');

        return $this->respond(new SettingResource($settings->fresh()), 'Settings updated');
    }

    /** The settings singleton, seeded with sensible defaults on first access. */
    private function settings(): Setting
    {
        return Setting::firstOrCreate([], [
            'restaurant_name' => 'Restaurant',
            'currency' => '₹',
            'tax_enabled' => true,
            'tax_rate' => 5,
            'discount_presets' => [5, 10, 15, 20],
            'enabled_payment_methods' => ['cash', 'card', 'upi'],
            'business_hours' => ['open' => '09:00 AM', 'close' => '10:00 PM'],
            'printer_config' => ['enabled' => true, 'printerName' => 'Kitchen Printer 1'],
            'receipt_footer' => 'Thank you for your business!',
        ]);
    }
}
