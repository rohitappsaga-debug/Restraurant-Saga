@php
    $navItems = [
        ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['icon' => 'clipboard-list', 'label' => 'Orders', 'route' => 'admin.orders'],
        ['icon' => 'grid-3x3', 'label' => 'Tables', 'route' => 'admin.tables'],
        ['icon' => 'bar-chart-3', 'label' => 'Insights', 'route' => 'admin.reports'],
    ];
@endphp

<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-background/95 backdrop-blur-xl border-t border-white/5 px-2 py-2 pb-6 flex justify-around items-center z-50 shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.5)]">
    @foreach($navItems as $item)
        @php
            $isActive = request()->routeIs($item['route']);
        @endphp
        <a 
            href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" 
            wire:navigate.hover
            class="flex flex-col items-center gap-1 w-20 transition-all duration-300 {{ $isActive ? 'text-amber-700 dark:text-amber-400 scale-110' : 'text-muted-foreground' }}"
        >
            <i data-lucide="{{ $item['icon'] }}" class="size-6"></i>
            <span class="text-[10px] font-black uppercase tracking-widest">{{ $item['label'] }}</span>
            @if($isActive)
                <div class="absolute -bottom-1 size-1 bg-amber-600 rounded-full shadow-glow"></div>
            @endif
        </a>
    @endforeach
</nav>
