<?php

namespace App\Livewire\Waiter;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $selectedRole = 'waiter';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
        'selectedRole' => 'required|in:waiter,manager',
    ];

    #[Layout('layouts.guest')]
    public function render()
    {
        $settings = Setting::current();
        return view('livewire.waiter.login', [
            'restaurantName' => $settings->restaurant_name ?? 'Restaurant Management System',
        ]);
    }

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $user = Auth::user();

            if ($user->role->value === $this->selectedRole) {
                session()->regenerate();
                return $this->redirect(route($this->selectedRole === 'manager' ? 'admin.dashboard' : 'waiter.dashboard'), navigate: true);
            }

            Auth::logout();
            $this->addError('email', 'Access restricted to ' . $this->selectedRole . ' personnel.');
            return;
        }

        $this->addError('email', 'Invalid credentials.');
    }
}
