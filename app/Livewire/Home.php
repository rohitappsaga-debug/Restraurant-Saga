<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Home extends Component
{
    #[Layout('layouts.guest')]
    public function render()
    {
        $settings = Setting::first();
        return view('livewire.home', [
            'restaurantName' => $settings?->restaurant_name ?? 'Restaurant Management System',
        ]);
    }
}
