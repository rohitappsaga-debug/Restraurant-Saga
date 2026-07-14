@php
    $restaurantName = \App\Models\Setting::first()?->restaurant_name ?? 'Restaurant';
@endphp

<header class="sticky top-0 z-30 bg-background border-b border-border px-6 py-4 flex items-center justify-between transition-all duration-300">
    <div class="flex items-center gap-4">
        <button
            class="p-2 -ml-2 rounded-xl hover:bg-muted/50 transition-colors cursor-pointer lg:hidden text-muted-foreground"
            @click="isSidebarOpen = true"
            aria-label="Open navigation menu"
        >
            <i data-lucide="menu" class="size-6"></i>
        </button>
        <div>
            <h1 class="text-[15px] font-extrabold text-foreground leading-none tracking-tight">{{ $restaurantName }}</h1>
            <p class="text-[10px] text-muted-foreground uppercase tracking-[0.15em] font-bold mt-1.5">Terminal 04</p>
        </div>
    </div>
    
    <div class="flex items-center gap-2">
        <button
            @click="isCommandPaletteOpen = true"
            class="p-2.5 rounded-xl text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-all active:scale-90"
            aria-label="Open command palette"
        >
            <i data-lucide="search" class="size-5"></i>
        </button>
        <livewire:admin.theme-toggle />
        <livewire:admin.notification-bell />
    </div>
</header>
