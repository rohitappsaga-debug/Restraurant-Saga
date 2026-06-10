<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Settings extends Component
{
    #[On('theme-persisted')]
    public function syncTheme($theme)
    {
        $this->theme = $theme;
        
        $user = auth()->user();
        if ($user) {
            $user->update(['theme' => $theme]);
        }
    }

    public $restaurant_name;
    public $restaurant_address;
    public $gst_no;
    public $currency;
    public $tax_enabled;
    public $tax_rate;
    public $discount_presets = [];
    public $new_discount_preset = '';
    public $printer_enabled;
    public $printer_name;
    public $open_time;
    public $close_time;
    public $payment_methods = [];
    public $receipt_footer;
    public $theme;
    
    // Notification Toggles
    public $order_notifications = true;
    public $payment_notifications = true;
    public $low_stock_alerts = false;

    public function mount()
    {
        $settings = Setting::first();
        if (!$settings) {
            $settings = Setting::create([
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

        $this->restaurant_name = $settings->restaurant_name;
        $this->restaurant_address = $settings->restaurant_address;
        $this->gst_no = $settings->gst_no;
        $this->currency = $settings->currency;
        $this->tax_enabled = $settings->tax_enabled;
        $this->tax_rate = (float) $settings->tax_rate;
        $this->discount_presets = $settings->discount_presets ?? [];
        $this->printer_enabled = $settings->printer_config['enabled'] ?? false;
        $this->printer_name = $settings->printer_config['printerName'] ?? '';
        $this->open_time = $settings->business_hours['open'] ?? '09:00 AM';
        $this->close_time = $settings->business_hours['close'] ?? '10:00 PM';
        $this->payment_methods = $settings->enabled_payment_methods ?? [];
        $this->receipt_footer = $settings->receipt_footer;
        $this->order_notifications = $settings->notification_preferences['order'] ?? true;
        $this->payment_notifications = $settings->notification_preferences['payment'] ?? true;
        $this->low_stock_alerts = $settings->notification_preferences['low_stock'] ?? false;
        $this->theme = auth()->user()->theme ?? 'light';
    }



    public function addDiscountPreset()
    {
        $val = (int) $this->new_discount_preset;
        if ($val > 0 && $val <= 100 && !in_array($val, $this->discount_presets)) {
            $this->discount_presets[] = $val;
            sort($this->discount_presets);
        }
        $this->new_discount_preset = '';
    }

    public function removeDiscountPreset($v)
    {
        $this->discount_presets = array_values(array_filter($this->discount_presets, fn($val) => $val != $v));
    }

    public function save()
    {
        $this->validate([
            'restaurant_name' => 'required|string|max:255',
            'tax_rate' => 'numeric|min:0|max:100',
        ]);

        $settings = Setting::first();
        $settings->update([
            'restaurant_name' => $this->restaurant_name,
            'restaurant_address' => $this->restaurant_address,
            'gst_no' => $this->gst_no,
            'currency' => $this->currency,
            'tax_enabled' => $this->tax_enabled,
            'tax_rate' => $this->tax_rate,
            'discount_presets' => $this->discount_presets,
            'printer_config' => [
                'enabled' => $this->printer_enabled,
                'printerName' => $this->printer_name
            ],
            'business_hours' => [
                'open' => $this->open_time,
                'close' => $this->close_time
            ],
            'enabled_payment_methods' => $this->payment_methods,
            'receipt_footer' => $this->receipt_footer,
            'notification_preferences' => [
                'order' => $this->order_notifications,
                'payment' => $this->payment_notifications,
                'low_stock' => $this->low_stock_alerts,
            ],
        ]);

        // Clear settings cache if any
        Cache::forget('app_settings');

        $this->dispatch('notify', ['message' => 'Settings persisted successfully', 'type' => 'success']);
    }

    public function getSystemInfoProperty()
    {
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Healthy';
        } catch (\Exception $e) {
            $dbStatus = 'Unreachable';
        }

        return [
            'version' => '1.5.8',
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db_driver' => DB::connection()->getDriverName(),
            'db_health' => $dbStatus,
            'last_backup' => 'Scheduled Daily @ 02:00 AM',
        ];
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'system' => $this->systemInfo
        ])->layout('layouts.admin');
    }
}
