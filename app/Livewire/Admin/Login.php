<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    #[Layout('layouts.guest')]
    public function render()
    {
        $settings = Setting::first();
        $restaurantName = $settings?->restaurant_name ?? 'Restaurant Management System';
        return view('livewire.admin.login', [
            'restaurantName' => $restaurantName
        ]);
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return $this->redirect(session()->pull('url.intended', route('admin.dashboard')), navigate: true);
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }
}
