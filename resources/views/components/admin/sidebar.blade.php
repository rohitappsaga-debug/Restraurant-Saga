@php
    $restaurantName = \App\Models\Setting::first()?->restaurant_name ?? 'Restaurant';
    $user = auth()->user();
    
    $menuItems = [
        ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['icon' => 'utensils', 'label' => 'Menu', 'route' => 'admin.menu'],
        ['icon' => 'layers', 'label' => 'Categories', 'route' => 'admin.categories'],
        ['icon' => 'grid-3x3', 'label' => 'Tables', 'route' => 'admin.tables'],
        ['icon' => 'clipboard-list', 'label' => 'Orders', 'route' => 'admin.orders'],
        ['icon' => 'bar-chart-3', 'label' => 'Sales Reports', 'route' => 'admin.reports'],
        ['icon' => 'users', 'label' => 'Users', 'route' => 'admin.users'],
        ['icon' => 'banknote', 'label' => 'Billing', 'route' => 'admin.billing'],
        ['icon' => 'settings', 'label' => 'Settings', 'route' => 'admin.settings'],
    ];
@endphp

<!-- Mobile Overlay -->
<div 
    x-show="isSidebarOpen" 
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-background/60 z-[60] lg:hidden" 
    @click="isSidebarOpen = false"
></div>

<aside 
    id="sidebar"
    class="fixed top-0 left-0 bottom-0 w-[280px] bg-sidebar border-r border-border transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] flex flex-col lg:translate-x-0"
    :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    x-cloak
>
    <!-- Logo Section -->
    <div class="px-6 py-8">
        <div class="flex items-center gap-3">
            <div class="size-10 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
                <i data-lucide="utensils" class="text-primary-foreground size-6"></i>
            </div>
            <div>
                <span class="block font-extrabold text-foreground text-lg tracking-tight leading-none uppercase">Restaurant</span>
                <span class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.1em] mt-1 block">Management Space</span>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto px-3 py-2 space-y-1 no-scrollbar">
        @foreach($menuItems as $item)
            @php
                $isActive = request()->routeIs($item['route']);
            @endphp
            <a 
                href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" 
                wire:navigate
                class="flex items-center gap-3.5 px-4 py-3 rounded-xl transition-all group {{ $isActive 
                    ? 'text-primary bg-primary/10' 
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' }}"
                @click="if (window.innerWidth < 1024) isSidebarOpen = false"
            >
                <i data-lucide="{{ $item['icon'] }}" class="size-6 {{ $isActive ? '' : 'group-hover:scale-110 transition-transform' }}"></i>
                <span class="font-bold text-[14px]">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Bottom Section -->
    <div class="p-6">
        <livewire:auth.logout />

        <div class="mt-6 flex items-center gap-3 px-1 border-t border-border/50 pt-6">
            <div class="relative">
                <img 
                    alt="Profile" 
                    class="size-10 rounded-full bg-muted p-0.5 border border-border" 
                    src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Admin') }}&background=6366f1&color=fff"
                />
                <span class="absolute bottom-0 right-0 size-3 bg-emerald-500 border-2 border-background rounded-full"></span>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-foreground truncate">{{ $user->name ?? 'Default Admin' }}</p>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">{{ $user->role->name ?? 'ADMIN' }}</p>
            </div>
        </div>
    </div>
</aside>
