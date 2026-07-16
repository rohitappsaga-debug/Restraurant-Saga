<?php

namespace App\Livewire\Kitchen;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public $email = '';
    public $password = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    #[Layout('layouts.guest')]
    public function render()
    {
        $settings = Setting::current();
        return view('livewire.kitchen.login', [
            'restaurantName' => $settings->restaurant_name ?? 'Restaurant Management System',
        ]);
    }

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $user = Auth::user();

            if ($user->role->value === 'kitchen') {
                session()->regenerate();
                return $this->redirect(route('kitchen.dashboard'), navigate: true);
            }

            Auth::logout();
            $this->addError('email', 'Access restricted to kitchen personnel.');
            return;
        }

        $this->addError('email', 'Invalid credentials.');
    }
}
