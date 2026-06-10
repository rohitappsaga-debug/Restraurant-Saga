<div class="space-y-8" wire:poll.5s>
    <!-- Header -->
    <div class="bg-card/60 backdrop-blur-md border-b border-border p-6 sticky top-0 z-50 shadow-sm rounded-b-[2.5rem]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black tracking-tighter text-foreground flex items-center gap-3">
                    <span class="size-3 rounded-full bg-primary animate-pulse"></span>
                    Kitchen Display
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-foreground/60 text-sm font-bold" wire:ignore>
                        <span x-data="{ time: '{{ now()->format('h:i:s A') }}' }" x-init="setInterval(() => { time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }) }, 1000)" x-text="time"></span>
                    </p>
                    <span class="text-foreground/40">—</span>
                    <p class="text-foreground/60 text-sm font-bold">
                        <span class="text-foreground">{{ $counts['all'] }}</span> Active Orders
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    wire:click="$refresh"
                    class="h-10 px-4 rounded-xl bg-card border border-border/50 text-foreground hover:bg-muted flex items-center justify-center gap-2 transition-all font-bold text-xs"
                    title="Refresh Orders"
                >
                    <i data-lucide="refresh-cw" class="size-3.5"></i>
                    Refresh
                </button>

                <button 
                    @click="$store.theme.toggle()"
                    class="h-10 px-4 rounded-xl bg-card border border-border/50 text-foreground hover:bg-muted flex items-center justify-center gap-2 transition-all font-bold text-xs"
                >
                    <template x-if="$store.theme.current === 'dark'">
                        <div class="flex items-center gap-2">
                            <i data-lucide="sun" class="size-3.5"></i>
                            <span>Light</span>
                        </div>
                    </template>
                    <template x-if="$store.theme.current === 'light'">
                        <div class="flex items-center gap-2">
                            <i data-lucide="moon" class="size-3.5"></i>
                            <span>Dark</span>
                        </div>
                    </template>
                </button>

                <button 
                    wire:click="setView('{{ $view === 'orders' ? 'menu' : 'orders' }}')"
                    class="h-10 px-4 rounded-xl bg-card border border-border/50 text-foreground hover:bg-muted flex items-center justify-center gap-2 transition-all font-bold text-xs"
                >
                    <i data-lucide="{{ $view === 'orders' ? 'utensils' : 'list-checks' }}" class="size-3.5"></i>
                    {{ $view === 'orders' ? 'Manage Menu' : 'View Orders' }}
                </button>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="h-10 px-4 rounded-xl bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center border border-rose-500/20 font-bold text-xs">
                        <i data-lucide="log-out" class="size-3.5 mr-2"></i>
                        Exit
                    </button>
                </form>
            </div>
        </div>

        @if($view === 'orders')
            <!-- Status Tabs -->
            <div class="flex gap-4 mt-8 overflow-x-auto pb-2 scrollbar-hide">
                @foreach(['all', 'pending', 'preparing' => 'cooking', 'ready', 'served'] as $key => $label)
                    @php 
                        $statusKey = is_numeric($key) ? $label : $key;
                        $styles = match($statusKey) {
                            'all' => $activeTab === $statusKey ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'bg-secondary border-border text-muted-foreground hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-500/30',
                            'sent', 'pending' => $activeTab === $statusKey ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-500/20' : 'bg-secondary border-border text-muted-foreground hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-500/30',
                            'preparing' => $activeTab === $statusKey ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-secondary border-border text-muted-foreground hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-500/30',
                            'ready' => $activeTab === $statusKey ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-secondary border-border text-muted-foreground hover:text-emerald-600 dark:hover:text-emerald-400 hover:border-emerald-500/30',
                            'served' => $activeTab === $statusKey ? 'bg-violet-600 border-violet-600 text-white shadow-lg shadow-violet-600/20' : 'bg-secondary border-border text-muted-foreground hover:text-violet-600 dark:hover:text-violet-400 hover:border-violet-500/30',
                            default => $activeTab === $statusKey ? 'bg-primary border-primary text-white shadow-lg shadow-primary/20' : 'bg-secondary border-border text-muted-foreground'
                        };
                    @endphp
                    <button
                        wire:key="tab-{{ $statusKey }}"
                        wire:click="setTab('{{ $statusKey }}')"
                        class="flex-1 min-w-[120px] h-11 rounded-xl transition-all border font-black text-[10px] uppercase tracking-[0.15em] flex items-center justify-center gap-2 {{ $styles }}"
                    >
                        {{ strtoupper($label) }}
                        <span class="rounded-full size-5 flex items-center justify-center text-[9px] font-black {{ $activeTab === $statusKey ? 'bg-black/20' : 'bg-muted text-muted-foreground' }}">
                            {{ $counts[$statusKey] }}
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <div class="flex-1 px-6 pb-20">
        @if($view === 'orders')
            @php
                $filteredSessions = $sessions->filter(function($session) use ($activeTab) {
                    if ($activeTab === 'all') return true;
                    return $session->orders->flatMap(fn($o) => $o->orderItems)->contains(function($item) use ($activeTab) {
                        $status = $item->status instanceof \App\Enums\OrderStatus ? $item->status->value : (is_object($item->status) ? $item->status->value : $item->status);
                        if ($activeTab === 'pending') {
                            return in_array($status, ['pending', 'sent']);
                        }
                        return $status === $activeTab;
                    });
                });
            @endphp

            @if($filteredSessions->isEmpty())
                <div class="flex flex-col items-center justify-center py-40 text-muted-foreground/30">
                    <div class="size-32 bg-muted/20 rounded-full flex items-center justify-center mb-6">
                        <i data-lucide="check-circle" class="size-16"></i>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-foreground/50">All caught up!</p>
                    <p class="text-lg font-medium mt-2">No active tables in this status</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8 items-start">
                    @foreach($filteredSessions as $session)
                        @php
                            $allOrderItems = $session->orders->flatMap(fn($o) => $o->orderItems);
                            
                            $displayItems = $allOrderItems->filter(function($item) use ($activeTab) {
                                $status = $item->status instanceof \App\Enums\OrderStatus ? $item->status->value : (is_object($item->status) ? $item->status->value : $item->status);
                                
                                if ($activeTab === 'all') return in_array($status, ['pending', 'sent', 'preparing', 'ready', 'served']);
                                if ($activeTab === 'pending') return in_array($status, ['pending', 'sent']);
                                return $status === $activeTab;
                            });
                            
                            $firstOrder = $session->orders->first();
                            $minutesElapsed = $firstOrder ? $firstOrder->created_at->diffInMinutes(now()) : 0;
                            $isHeavyDelay = $minutesElapsed >= 25;
                            $isWarningDelay = $minutesElapsed >= 15;
                            
                            $cardStatusClasses = 'border-border bg-card';
                            if ($isHeavyDelay) $cardStatusClasses = 'border-rose-500/30 bg-rose-500/[0.03] ring-1 ring-rose-500/20';
                            elseif($isWarningDelay) $cardStatusClasses = 'border-amber-500/30 bg-amber-500/[0.03] ring-1 ring-amber-500/20';
                        @endphp

                        <div 
                            wire:key="session-{{ $session->id }}"
                            class="flex flex-col rounded-2xl border backdrop-blur-sm transition-all duration-300 {{ $cardStatusClasses }} shadow-sm hover:shadow-md"
                        >
                            <!-- Header -->
                            <div class="p-6 border-b border-border/50">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-3 mb-1">
                                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Table</span>
                                            <div class="size-1.5 rounded-full {{ $isHeavyDelay ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500' }}"></div>
                                        </div>
                                        <h3 class="text-5xl font-black text-foreground tracking-tighter">{{ $session->table->number }}</h3>
                                    </div>

                                    <div class="flex flex-col items-end gap-3">
                                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary/80 border border-border">
                                            <i data-lucide="clock" class="size-3.5 {{ $isHeavyDelay ? 'text-rose-500' : 'text-zinc-500' }}"></i>
                                            <span class="text-[11px] font-black {{ $isHeavyDelay ? 'text-rose-500' : 'text-zinc-700' }} uppercase tracking-wider">
                                                {{ (int) $minutesElapsed }} Mins
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black text-zinc-500 uppercase tracking-widest font-mono">ID: {{ strtoupper(substr($session->id, -6)) }}</span>
                                            <button 
                                                wire:click="forceCloseSession('{{ $session->id }}')"
                                                wire:confirm="Are you sure you want to clear this table from the kitchen display? This is usually done by the waiter at checkout."
                                                class="size-8 rounded-lg bg-secondary hover:bg-rose-500/10 text-zinc-500 hover:text-rose-500 transition-colors flex items-center justify-center border border-border"
                                                title="Force Clear Table"
                                            >
                                                <i data-lucide="trash-2" class="size-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div class="p-6 space-y-3">
                                @foreach($displayItems->sortBy('status') as $item)
                                    @php
                                        $statusValue = $item->status instanceof \App\Enums\OrderStatus ? $item->status->value : $item->status;
                                        $isReady = $statusValue === 'ready';
                                        $isPreparing = $statusValue === 'preparing';
                                        $isServed = $statusValue === 'served';
                                        
                                        $rowClasses = 'bg-secondary/40 border-border/60';
                                        if ($isReady) $rowClasses = 'bg-emerald-500/[0.05] border-emerald-500/30';
                                        elseif ($isPreparing) $rowClasses = 'bg-blue-500/[0.05] border-blue-500/30';
                                        elseif ($isServed) $rowClasses = 'opacity-75 grayscale-[0.3] bg-zinc-100 dark:bg-zinc-900/40';
                                    @endphp

                                    <div class="group p-4 rounded-xl border {{ $rowClasses }} transition-all hover:bg-slate-800/40">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex items-center gap-4 min-w-0">
                                                <div class="flex-none size-10 rounded-lg flex items-center justify-center font-black {{ $isReady ? 'bg-emerald-500 text-white' : ($isPreparing ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400') }} border border-border/50">
                                                    {{ $item->quantity }}<span class="text-[10px] opacity-60 ml-0.5">x</span>
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <h4 class="text-base font-black text-zinc-900 dark:text-zinc-100 truncate leading-tight">{{ $item->menuItem->name }}</h4>
                                                    @if($item->kot)
                                                        <span class="text-[9px] font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mt-0.5">Batch #{{ $item->kot->batch_number }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="flex-none">
                                                @if($statusValue === 'sent' || $statusValue === 'pending')
                                                    <button 
                                                        wire:click="updateItemStatus('{{ $item->order_id }}', '{{ $item->id }}', 'preparing')"
                                                        class="h-9 px-4 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-black text-[10px] uppercase tracking-widest transition-all flex items-center gap-2 shadow-sm shadow-blue-600/10"
                                                    >
                                                        <i data-lucide="play" class="size-3.5 fill-current"></i>
                                                        Start
                                                    </button>
                                                @elseif($statusValue === 'preparing')
                                                    <button 
                                                        wire:click="updateItemStatus('{{ $item->order_id }}', '{{ $item->id }}', 'ready')"
                                                        class="h-9 px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-black text-[10px] uppercase tracking-widest transition-all flex items-center gap-2 shadow-sm shadow-emerald-600/10"
                                                    >
                                                        <i data-lucide="check" class="size-3.5"></i>
                                                        Finish
                                                    </button>
                                                @elseif($statusValue === 'ready')
                                                    <div class="h-9 px-3 bg-emerald-500/10 text-emerald-500 rounded-lg font-black text-[10px] uppercase tracking-widest flex items-center gap-2 border border-emerald-500/20">
                                                        <i data-lucide="check-circle" class="size-3.5"></i>
                                                        Ready
                                                    </div>
                                                @else
                                                    <div class="h-9 px-3 bg-slate-800/50 text-slate-500 rounded-lg font-black text-[10px] uppercase tracking-widest flex items-center gap-2 border border-slate-700/30">
                                                        <i data-lucide="user-check" class="size-3.5"></i>
                                                        Served
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if($item->notes)
                                            <div class="mt-3 px-3 py-2 rounded-lg bg-amber-500/[0.05] border border-amber-500/20 flex items-center gap-2">
                                                <i data-lucide="message-square" class="size-3.5 text-amber-500"></i>
                                                <span class="text-[11px] font-medium text-amber-200/80">{{ $item->notes }}</span>
                                            </div>
                                        @endif

                                        @if($item->modifiers)
                                            <div class="flex flex-wrap gap-1.5 mt-3">
                                                @foreach($item->modifiers as $mod)
                                                    <span class="px-2 py-1 rounded-lg bg-slate-800 text-[9px] font-bold text-slate-400 uppercase tracking-tight">+ {{ is_array($mod) ? $mod['name'] : $mod }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <!-- Menu Manager View -->
            <div class="p-6 space-y-8">
                <div class="flex flex-col md:flex-row gap-6 items-center justify-between">
                    <div class="relative w-full md:w-96 group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40 group-focus-within:text-primary transition-colors"></i>
                        <input
                            type="text"
                            placeholder="Search menu items..."
                            wire:model.live.debounce.300ms="searchTerm"
                            class="w-full h-14 pl-12 pr-4 bg-card border border-border/50 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-primary/10 transition-all outline-none"
                        />
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-2 w-full md:w-auto scrollbar-hide">
                        @foreach($menuCategories as $category)
                            <button
                                wire:click="$set('selectedMenuCategory', '{{ $category }}')"
                                class="h-10 px-6 rounded-full font-bold text-xs uppercase tracking-widest whitespace-nowrap transition-all {{ $selectedMenuCategory === $category ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-secondary text-muted-foreground border border-border/50 hover:bg-muted' }}"
                            >
                                {{ $category }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6">
                    @forelse($menuItems as $item)
                        <div class="group aspect-square relative p-5 bg-card border-2 border-border/50 rounded-[2.5rem] flex flex-col transition-all duration-300 {{ $item->available ? 'hover:border-primary shadow-sm hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1' : 'border-rose-500/30 bg-rose-500/[0.02]' }}">
                            <!-- Image Container -->
                            <div class="flex-1 w-full rounded-[1.75rem] mb-4 overflow-hidden border border-border/10 bg-muted/30 relative shrink-0">
                                @if($item->thumbnail_url)
                                    <img src="{{ $item->thumbnail_url }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-primary/5 to-primary/10 flex items-center justify-center">
                                        <span class="text-3xl font-black text-primary/10">{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                    </div>
                                @endif

                                <!-- Status Badge -->
                                <div class="absolute top-3 right-3">
                                    <span @class([
                                        'px-2.5 py-1 rounded-full text-[8px] font-black uppercase tracking-widest shadow-sm border backdrop-blur-md',
                                        'bg-emerald-500/90 text-white border-emerald-400' => $item->available,
                                        'bg-rose-500/90 text-white border-rose-400' => !$item->available
                                    ])>
                                        {{ $item->available ? 'Available' : 'Out' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex flex-col shrink-0">
                                <div class="mb-3 px-1">
                                    <h3 class="font-black text-sm text-foreground leading-tight truncate tracking-tight">{{ $item->name }}</h3>
                                    <p class="text-[9px] font-black text-muted-foreground/60 uppercase tracking-widest mt-0.5 truncate">{{ $item->category }}</p>
                                </div>

                                <button
                                    wire:click="toggleAvailability('{{ $item->id }}')"
                                    class="w-full h-11 rounded-2xl font-black text-[9px] uppercase tracking-[0.15em] transition-all flex items-center justify-center gap-2 {{ $item->available 
                                        ? 'bg-muted/40 text-muted-foreground hover:bg-rose-500 hover:text-white hover:border-rose-500 border border-border/50' 
                                        : 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20 border border-emerald-500/50' }}"
                                >
                                    @if($item->available)
                                        <i data-lucide="x-circle" class="size-3.5"></i>
                                        Set Out of Stock
                                    @else
                                        <i data-lucide="check-circle" class="size-3.5"></i>
                                        Set Available
                                    @endif
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-32 text-center">
                            <div class="size-20 bg-muted/30 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i data-lucide="search-x" class="size-10 text-muted-foreground/20"></i>
                            </div>
                            <p class="text-muted-foreground/40 font-black uppercase tracking-widest text-xs">No menu items found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @script
    <script>
        $wire.on('notify', (data) => {
            const payload = data[0] || data;
            if (window.showToast) {
                window.showToast(payload.message, payload.type);
            }
        });

        // Icon Re-render with stability
        let iconRequest;
        const refreshIcons = () => {
             if (iconRequest) cancelAnimationFrame(iconRequest);
             iconRequest = requestAnimationFrame(() => {
                if (window.lucide) lucide.createIcons();
             });
        };

        Livewire.hook('morph.updated', refreshIcons);
        document.addEventListener('livewire:navigated', refreshIcons);
        
        // Initial call
        refreshIcons();
    </script>
    @endscript
</div>
