<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!app()->runningInConsole()) {
            try {
                $currency = Setting::current()?->currency ?? '₹';
                View::share('currency', $currency);
            } catch (\Exception $e) {
                // Ignore if table doesn't exist yet
            }
        }
    }
}
