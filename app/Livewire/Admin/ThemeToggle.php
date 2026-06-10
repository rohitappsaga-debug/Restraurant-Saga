<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

use Livewire\Attributes\On;

class ThemeToggle extends Component
{
    public $theme;

    #[On('theme-persisted')]
    public function persistTheme($theme)
    {
        $this->theme = $theme;
        $user = Auth::user();
        if ($user) {
            $user->theme = $theme;
            $user->save();
        }
    }

    public function mount()
    {
        $this->theme = Auth::user()->theme ?? 'light';
    }



    public function render()
    {
        return <<<'HTML'
        <div x-data>
            <template x-if="$store.theme">
                <button 
                    type="button"
                    @click="$store.theme.toggle()"
                    class="p-2.5 rounded-xl text-muted-foreground hover:text-foreground hover:bg-muted transition-all active:scale-90 group relative"
                    title="Toggle Theme"
                >
                    <div class="relative size-5 overflow-hidden font-bold">
                        <div class="absolute inset-0 flex items-center justify-center transition-all duration-500"
                            :class="$store.theme.current === 'dark' ? '-translate-y-10 rotate-90 opacity-0' : 'translate-y-0 rotate-0 opacity-100'">
                            <i data-lucide="sun" class="size-5 text-amber-500"></i>
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center transition-all duration-500"
                            :class="$store.theme.current === 'dark' ? 'translate-y-0 rotate-0 opacity-100' : 'translate-y-10 -rotate-90 opacity-0'">
                            <i data-lucide="moon" class="size-5 text-indigo-400"></i>
                        </div>
                    </div>
                    
                    <span class="absolute -top-1 -right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-20"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary/40"></span>
                    </span>
                </button>
            </template>
        </div>
        HTML;
    }
}
