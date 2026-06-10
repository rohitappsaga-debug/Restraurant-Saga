<div class="space-y-10 pb-12 relative min-h-screen">
    <!-- Subtle Loading Overlay for smooth transitions -->
    <div wire:loading.delay.shortest wire:target="period" class="absolute inset-x-0 -top-4 -bottom-4 bg-background/10 backdrop-blur-[2px] z-[60] flex items-center justify-center rounded-[3rem] transition-all duration-300">
        <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
    </div>
    <!-- Header/Overview Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tight">Dashboard Overview</h1>
            <p class="text-xs text-muted-foreground font-bold uppercase tracking-widest mt-1 opacity-70">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        
        <div class="flex items-center gap-4 bg-card p-1.5 rounded-[1.25rem] border border-border/50 shadow-sm">
            @foreach(['today', 'week', 'month', 'all'] as $p)
                <button 
                    wire:click="$set('period', '{{ $p }}')"
                    class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $period === $p ? 'bg-primary text-white shadow-lg shadow-primary/20 scale-105' : 'text-muted-foreground hover:bg-muted' }}"
                >
                    {{ $p }}
                </button>
            @endforeach
        </div>

        <div class="hidden lg:flex items-center gap-3 bg-card px-4 py-2.5 rounded-2xl border border-border shadow-sm">
             <div class="size-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_12px_rgba(16,185,129,0.5)]"></div>
             <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.15em] leading-none">Live Updates Active</span>
        </div>
    </div>

    <!-- Stat Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" wire:key="stats-grid-{{ $period }}">
        <div wire:key="stat-revenue-{{ $period }}">
            <x-admin.dashboard.stat-card 
                label="{{ ucfirst($period) }} Revenue" 
                :value="'₹' . number_format($dailyRevenue, 2)" 
                trend="{{ $period === 'today' ? 'Live' : 'Total' }}" 
                icon="banknote"
                variant="emerald"
            />
        </div>
        <div wire:key="stat-orders-{{ $period }}">
            <x-admin.dashboard.stat-card 
                label="{{ ucfirst($period) }} Orders" 
                :value="$todayOrders" 
                trend="{{ $period === 'today' ? 'Live' : 'Total' }}" 
                icon="clipboard-list"
                variant="blue"
            />
        </div>
        <div wire:key="stat-occupancy">
            <x-admin.dashboard.stat-card 
                label="Active Tables" 
                :value="$occupancyRate . '%'" 
                :trend="$occupancyRate . '% Occupancy'" 
                icon="grid-3x3"
                variant="orange"
            />
        </div>
    </div>

    <!-- Chart & Volume Section -->
    <div class="grid grid-cols-1 gap-8" wire:key="chart-section-{{ $period }}">
        <x-admin.dashboard.hourly-chart 
            :data="array_values($hourlySales)" 
            :labels="$chartLabels"
            :peakHour="$peakHour" 
            :title="$chartTitle"
            :subtitle="$chartSubTitle"
            :peakLabel="$peakLabel"
        />
    </div>

    <!-- Live Orders Section -->
    <livewire:admin.dashboard.live-orders />
</div>
