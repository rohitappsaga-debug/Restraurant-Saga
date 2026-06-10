<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return <<<'HTML'
            <button wire:click="logout" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl text-rose-500 hover:bg-rose-50 border border-rose-100 transition-all">
                <i data-lucide="log-out" class="size-6"></i>
                <span class="font-bold text-[14px]">Logout</span>
            </button>
        HTML;
    }
}
