<div class="flex flex-col min-h-screen bg-background text-foreground">
    <!-- Premium Global Header: Radiant Indigo -->
    <header class="bg-indigo-600 px-8 py-6 shrink-0 shadow-lg text-white sticky top-0 md:relative z-40 transition-colors duration-500">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-5">
                @if(in_array($view, ['menu', 'summary', 'bill', 'profile']))
                    <button wire:click="setView('{{ in_array($view, ['summary', 'bill']) ? 'menu' : 'home' }}')" class="size-11 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/10 flex items-center justify-center text-white transition-all shadow-sm active:scale-95">
                        <i data-lucide="arrow-left" class="size-5"></i>
                    </button>
                @endif
                
                <div class="flex flex-col">
                    <h1 class="text-2xl font-black tracking-tight leading-tight uppercase">
                        @if($view === 'profile') Profile @else
                            @if($view === 'home') Tables Overview @endif
                            @if($view === 'menu') Table {{ $this->selectedTablesLabel }} @endif
                            @if($view === 'summary') Table {{ $this->selectedTablesLabel }} @endif
                            @if($view === 'bill') Table {{ $this->selectedTablesLabel }} @endif
                            @if($view === 'alerts') Notifications @endif
                        @endif
                    </h1>
                    <div 
                        x-data="{ 
                            time: '',
                            updateTime() {
                                const now = new Date();
                                this.time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
                            }
                        }" 
                        x-init="updateTime(); setInterval(() => updateTime(), 1000)"
                        class="text-indigo-200 text-[10px] font-bold uppercase tracking-widest flex items-center gap-2"
                    >
                        <span x-text="time"></span>
                        @if($view === 'home')
                            <span class="opacity-40">—</span>
                            <span class="text-white">{{ $this->activeTablesCount }} Active Tables</span>
                        @else
                            <span class="opacity-40">—</span>
                            <span>
                                @if($view === 'menu') Browse Menu @endif
                                @if($view === 'summary') Order Review @endif
                                @if($view === 'bill') Checkout @endif
                                @if($view === 'alerts') Dashboard Updates @endif
                                @if($view === 'profile') Personal Settings @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">

                <!-- Theme Toggle Button -->
                <button 
                    @click="$store.theme.toggle()"
                    class="relative size-11 bg-white/10 hover:bg-white/20 rounded-2xl flex items-center justify-center text-white border border-white/10 transition-all active:scale-95 group overflow-hidden"
                    aria-label="Toggle Theme"
                >
                    <div class="absolute inset-0 flex items-center justify-center transition-all duration-500"
                        :class="$store.theme.current === 'dark' ? '-translate-y-12 rotate-90 opacity-0' : 'translate-y-0 rotate-0 opacity-100'">
                        <i data-lucide="sun" class="size-5 text-amber-300"></i>
                    </div>
                    
                    <div class="absolute inset-0 flex items-center justify-center transition-all duration-500"
                        :class="$store.theme.current === 'dark' ? 'translate-y-0 rotate-0 opacity-100' : 'translate-y-12 -rotate-90 opacity-0'">
                        <i data-lucide="moon" class="size-5 text-white"></i>
                    </div>
                </button>

                <button wire:click="$refresh" wire:loading.class="animate-spin" aria-label="Refresh" class="size-11 bg-white/10 hover:bg-white/20 rounded-2xl flex items-center justify-center text-white border border-white/10 transition-all active:scale-95 disabled:opacity-50">
                    <i data-lucide="refresh-cw" class="size-4" wire:loading.remove></i>
                    <div wire:loading class="size-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                </button>
                
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-xs font-black uppercase">{{ auth()->user()->name }}</span>
                    <span class="text-[9px] font-bold text-indigo-300 uppercase tracking-tighter">Waiter Terminal</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area: Responsive with fixed elements -->
    <main class="flex-1 overflow-hidden relative flex flex-col">
        <div class="flex-1 overflow-y-auto p-6 md:p-8 pb-40 no-scrollbar">
        @if($view === 'home')
            <div class="max-w-7xl mx-auto">
                <!-- Utilitarian Table Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                    @foreach($this->tables as $table)
                        @php
                            $statusColor = match($table->status->value) {
                                'free' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'occupied' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                'reserved' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                'cleaning' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'out_of_service' => 'bg-zinc-800 text-zinc-500 border-white/5',
                                default => 'bg-zinc-800 text-zinc-500 border-white/5'
                            };
                        @endphp
                        <div class="relative group">
                            <button 
                                wire:key="table-{{ $table->id }}"
                                wire:click="{{ $table->status->value === 'cleaning' ? '' : "toggleTableSelection('{$table->id}')" }}"
                                wire:loading.attr="disabled"
                                wire:target="toggleTableSelection('{{ $table->id }}')"
                                @class([
                                    'w-full flex flex-col justify-between p-6 rounded-3xl bg-card border transition-all text-left group relative disabled:opacity-50 shadow-sm hover:shadow-md',
                                    'border-border/60 hover:border-indigo-500/50' => $table->status->value !== 'cleaning' && !in_array($table->id, $selectedTableIds),
                                    'border-indigo-600 ring-2 ring-indigo-500/40 shadow-indigo-500/10' => in_array($table->id, $selectedTableIds),
                                    'border-blue-500/30 bg-blue-50/5 dark:bg-blue-900/5' => $table->status->value === 'cleaning'
                                ])
                            >
                                <div wire:loading wire:target="toggleTableSelection('{{ $table->id }}')" class="absolute inset-0 flex items-center justify-center bg-background/50 backdrop-blur-[2px] z-10 rounded-3xl">
                                    <div class="size-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                                <div class="flex items-start justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60 mb-1 font-mono">TBL-{{ $table->number }}</span>
                                        <span class="text-4xl font-black tracking-tighter text-foreground">{{ $table->number }}</span>
                                    </div>
                                    <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm {{ $statusColor }}">
                                        {{ str_replace('_', ' ', $table->status->value) }}
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <i data-lucide="users" class="size-4"></i>
                                        <span class="text-xs font-bold">{{ $table->capacity }}</span>
                                    </div>
                                    @if(in_array($table->id, $selectedTableIds))
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-indigo-600 text-white">
                                            <i data-lucide="check" class="size-3"></i>
                                            <span class="text-[9px] font-black uppercase tracking-widest">Selected</span>
                                        </div>
                                    @elseif($table->status->value === 'occupied')
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-indigo-500/10 text-indigo-500">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-indigo-500"></span>
                                            </span>
                                            <span class="text-[9px] font-black uppercase tracking-widest">Active</span>
                                        </div>
                                    @endif
                                </div>

                                 @if($table->has_ready_items && $table->status->value === 'occupied')
                                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 flex flex-col items-center animate-bounce z-20 pointer-events-none">
                                        <div class="bg-emerald-600 text-white text-[10px] font-black px-4 py-1.5 rounded-full shadow-[0_10px_25px_rgba(16,185,129,0.5)] border border-white/20 whitespace-nowrap uppercase tracking-widest">
                                            Order Ready!
                                        </div>
                                        <div class="size-2 bg-emerald-600 rotate-45 -mt-1 shadow-lg"></div>
                                    </div>
                                @endif
                            </button>

                            @if($table->status->value === 'cleaning')
                                <button 
                                    wire:click="markCleaned('{{ $table->id }}')"
                                    class="absolute -bottom-2 left-1/2 -translate-x-1/2 h-8 px-4 bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-black uppercase tracking-widest rounded-full shadow-lg transition-all active:scale-95 flex items-center gap-2 z-10"
                                >
                                    <i data-lucide="sparkles" class="size-3"></i>
                                    Ready
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Start Order CTA (multi-table selection) -->
                @if(count($selectedTableIds) > 0)
                    <div class="fixed bottom-24 left-0 right-0 z-40 px-8 pointer-events-none">
                        <div class="max-w-7xl mx-auto flex justify-center">
                            <button
                                wire:click="startOrder"
                                class="pointer-events-auto h-16 px-10 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl flex items-center gap-4 text-xs font-black uppercase tracking-widest shadow-[0_15px_40px_rgba(79,70,229,0.4)] border border-white/10 transition-all active:scale-95 animate-in slide-in-from-bottom-4"
                            >
                                <i data-lucide="clipboard-plus" class="size-5"></i>
                                Start Order — Table {{ $this->selectedTablesLabel }}
                                <span class="px-2.5 py-1 bg-white/15 rounded-lg text-[10px]">{{ count($selectedTableIds) }} {{ count($selectedTableIds) === 1 ? 'table' : 'tables' }}</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

        @elseif($view === 'menu')
            <div class="flex flex-col min-h-full max-w-7xl mx-auto w-full px-8 pb-32">
                <!-- Premium Action Container: SaaS Design -->
                <div class="bg-card rounded-[2rem] border border-border/60 shadow-sm p-6 md:p-8 mb-10 transition-all duration-300">
                    <div class="max-w-4xl mx-auto space-y-8">
                        <!-- Centered Search Alignment -->
                        <div class="relative group">
                            <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-indigo-600 transition-colors"></i>
                            <input 
                                wire:model.live.debounce.300ms="menuSearch"
                                type="text" 
                                placeholder="Search our delicious menu..." 
                                class="w-full h-14 pl-14 pr-8 bg-background border border-border/80 rounded-full text-base font-semibold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 transition-all outline-none placeholder:text-muted-foreground/60 shadow-inner"
                            >
                        </div>

                        <!-- Flex-Wrapping Category Chips -->
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <button 
                                wire:click="selectCategory(null)"
                                wire:key="cat-all"
                                class="px-7 py-3 rounded-full text-[11px] font-black uppercase tracking-[0.15em] transition-all whitespace-nowrap {{ !$selectedCategoryId ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-500/20' : 'bg-secondary text-muted-foreground border border-border hover:bg-muted hover:text-foreground active:scale-95' }}"
                            >
                                All Items
                            </button>
                            @foreach($this->categories as $category)
                                <button 
                                    wire:key="cat-{{ $category->id }}"
                                    wire:click="selectCategory('{{ $category->id }}')"
                                    class="px-7 py-3 rounded-full text-[11px] font-black uppercase tracking-[0.15em] transition-all whitespace-nowrap {{ $selectedCategoryId == $category->id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-500/20' : 'bg-secondary text-muted-foreground border border-border hover:bg-muted hover:text-foreground active:scale-95' }}"
                                >
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Item Grid with Performance Optimizations -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->menuItems as $item)
                        @php 
                            $cartItemKey = collect($cart)->search(fn($c) => $c['item_id'] === $item->id);
                            $catName = strtolower($item->categoryRel->name ?? '');
                            $emoji = match(true) {
                                str_contains($catName, 'pizza') => '🍕',
                                str_contains($catName, 'burger') => '🍔',
                                str_contains($catName, 'dessert') => '🍰',
                                str_contains($catName, 'beverage') => '🥤',
                                str_contains($catName, 'pasta') => '🍝',
                                str_contains($catName, 'salad') => '🥗',
                                default => '🍽️'
                            };
                        @endphp
                        <div 
                            wire:key="menu-item-{{ $item->id }}"
                            class="p-4 rounded-3xl bg-card border border-border/50 hover:border-indigo-500/30 transition-all group shadow-sm hover:shadow-md flex flex-col justify-between h-full group {{ !$item->available ? 'opacity-60 saturate-0' : '' }}"
                        >
                            <div class="flex gap-5">
                                <!-- Image Container: Fixed Square -->
                                <div class="size-24 bg-muted rounded-2xl flex items-center justify-center shrink-0 relative border border-border/30 overflow-hidden shadow-inner">
                                    <span class="text-3xl drop-shadow-lg">{{ $item->thumbnail_url ? '' : $emoji }}</span>
                                    @if($item->thumbnail_url)
                                        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    @endif
                                    
                                    <!-- Status Overlay -->
                                    <div class="absolute top-2 right-2 size-5 bg-white/90 backdrop-blur-sm rounded-md flex items-center justify-center border border-zinc-200 shadow-sm">
                                        @if($item->is_veg)
                                            <div class="border border-green-600 p-[1.5px] rounded-[3px]"><div class="size-2 bg-green-600 rounded-full"></div></div>
                                        @else
                                            <div class="border border-rose-600 p-[1.5px] rounded-[3px]"><div class="size-2 bg-rose-600 rounded-full"></div></div>
                                        @endif
                                    </div>

                                    @if(!$item->available)
                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                            <span class="bg-rose-500 text-white text-[8px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full rotate-[-15deg] shadow-lg">OUT</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Text Content Container -->
                                <div class="flex-1 min-w-0 pr-1">
                                    <h4 class="text-base font-black text-foreground leading-tight line-clamp-1 mb-1">{{ $item->name }}</h4>
                                    <p class="text-xs text-muted-foreground leading-relaxed line-clamp-2">{{ $item->description }}</p>
                                    
                                    <div class="flex items-center gap-3 mt-3">
                                        <span class="text-xl font-black text-indigo-600">{{ $currency }}{{ number_format($item->price, 0) }}</span>
                                        @if($item->preparation_time)
                                            <div class="flex items-center gap-1.5 text-[9px] font-bold text-muted-foreground/60 uppercase tracking-widest">
                                                <i data-lucide="clock" class="size-3"></i>
                                                {{ $item->preparation_time }} MIN
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions Section -->
                            <div class="mt-5 pt-4 border-t border-border/30">
                                @if(!$item->available)
                                    <div class="w-full h-12 flex items-center justify-center rounded-2xl bg-secondary/50 border border-border border-dashed text-[10px] font-black uppercase tracking-widest text-muted-foreground grayscale">
                                        Currently Unavailable
                                    </div>
                                @elseif($cartItemKey !== false)
                                    <div class="flex items-center justify-between bg-secondary/30 p-1.5 rounded-2xl border border-border/50">
                                        <button wire:click="updateQuantity('{{ $cartItemKey }}', -1)" wire:loading.attr="disabled" class="size-10 flex items-center justify-center rounded-xl bg-card border border-border text-rose-500 hover:bg-rose-50 transition-colors shadow-sm disabled:opacity-50 active:scale-95">
                                            <i data-lucide="minus" class="size-5"></i>
                                        </button>
                                        <div class="flex flex-col items-center">
                                            <span class="text-sm font-black text-foreground">{{ $cart[$cartItemKey]['quantity'] }}</span>
                                            <span class="text-[8px] font-bold uppercase text-muted-foreground tracking-tighter">In Cart</span>
                                        </div>
                                        <button wire:click="updateQuantity('{{ $cartItemKey }}', 1)" wire:loading.attr="disabled" class="size-10 flex items-center justify-center rounded-xl bg-card border border-border text-indigo-600 hover:bg-indigo-50 transition-colors shadow-sm disabled:opacity-50 active:scale-95">
                                            <i data-lucide="plus" class="size-5"></i>
                                        </button>
                                    </div>
                                @else
                                    <button 
                                        wire:click="addToCart('{{ $item->id }}')" 
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart('{{ $item->id }}')"
                                        class="w-full h-12 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-black uppercase tracking-[0.2em] transition-all shadow-[0_8px_20px_-8px_rgba(79,70,229,0.5)] active:scale-95 disabled:opacity-80 flex items-center justify-center gap-3"
                                    >
                                        <span wire:loading.remove wire:target="addToCart('{{ $item->id }}')">Add to Order</span>
                                        <div wire:loading wire:target="addToCart('{{ $item->id }}')" class="size-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @elseif($view === 'summary')
            <div class="flex flex-col min-h-screen bg-background pb-96">
                <!-- Scrollable Items Area -->
                <div class="flex-1 p-6 pb-32">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-7xl mx-auto">
                        @foreach($cart as $key => $item)
                            @php 
                                $catName = strtolower($item['category'] ?? '');
                                $emoji = match(true) {
                                    str_contains($catName, 'pizza') => '🍕',
                                    str_contains($catName, 'burger') => '🍔',
                                    str_contains($catName, 'dessert') => '🍰',
                                    str_contains($catName, 'beverage') || str_contains($catName, 'drink') => '🥤',
                                    str_contains($catName, 'pasta') => '🍝',
                                    str_contains($catName, 'salad') => '🥗',
                                    default => '🍽️'
                                };
                            @endphp
                            <div class="p-4 rounded-2xl bg-card border border-border flex flex-col gap-4">
                                <div class="flex gap-4">
                                    <div class="size-16 rounded-xl bg-muted/30 flex items-center justify-center shrink-0 border border-border relative overflow-hidden">
                                        @if($item['thumbnail_url'] ?? null)
                                            <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-2xl">{{ $emoji }}</span>
                                        @endif
                                        <div class="absolute -top-1 -right-1 size-4 bg-white rounded-sm flex items-center justify-center border border-zinc-200 z-10">
                                            @if($item['is_veg'] ?? false)
                                                <div class="border border-green-600 p-[1px] rounded-[2px]"><div class="size-1.5 bg-green-600 rounded-full"></div></div>
                                            @else
                                                <div class="border border-rose-600 p-[1px] rounded-[2px]"><div class="size-1.5 bg-rose-600 rounded-full"></div></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-sm font-black text-foreground truncate">{{ $item['name'] }}</h4>
                                            <button wire:click="updateQuantity('{{ $key }}', -100)" class="text-rose-500/30 hover:text-rose-500 transition-colors">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs font-black text-indigo-500">{{ $currency }}{{ number_format($item['price'], 0) }} × {{ $item['quantity'] }}</span>
                                            <span class="text-sm font-black text-foreground">{{ $currency }}{{ number_format($item['price'] * $item['quantity'], 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Notes Input -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-600 ml-1">Special Instructions</label>
                                    <input 
                                        wire:model.blur="cart.{{ $key }}.notes" 
                                        type="text" 
                                        placeholder="e.g. less spicy, no onions..." 
                                        class="w-full h-11 px-4 bg-muted/30 border border-border rounded-xl text-xs text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:border-indigo-500/50 transition-all font-medium focus:bg-muted/50"
                                    >
                                </div>

                                <!-- Quantity Toggles inside review -->
                                <div class="flex items-center justify-between pt-2">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-zinc-600">Adjust Order</span>
                                    <div class="flex items-center gap-3 bg-secondary p-1 rounded-xl border border-border">
                                        <button wire:click="updateQuantity('{{ $key }}', -1)" class="size-7 flex items-center justify-center rounded-lg bg-card border border-border text-rose-500 hover:bg-rose-500/10 transition-all">
                                            <i data-lucide="minus" class="size-3.5"></i>
                                        </button>
                                        <span class="text-xs font-black w-4 text-center text-foreground">{{ $item['quantity'] }}</span>
                                        <button wire:click="updateQuantity('{{ $key }}', 1)" class="size-7 flex items-center justify-center rounded-lg bg-card border border-border text-indigo-500 hover:bg-indigo-400/10 transition-all">
                                            <i data-lucide="plus" class="size-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Actions for Order Summary -->
                <div class="fixed bottom-20 left-0 right-0 z-30 bg-background border-t border-border px-8 py-6 shadow-[0_-10px_40px_rgba(0,0,0,0.4)]">
                    <div class="max-w-7xl mx-auto space-y-4">
                        <div class="space-y-1.5 border-b border-white/5 pb-4">
                            <div class="flex items-center justify-between text-[11px] font-bold text-zinc-500 uppercase tracking-widest">
                                <span>Subtotal</span>
                                <span>{{ $currency }}{{ number_format($totals['subtotal'], 0) }}</span>
                            </div>
                            @if($totals['taxTotal'] > 0)
                                <div class="flex items-center justify-between text-[11px] font-bold text-zinc-500 uppercase tracking-widest font-mono">
                                    <span>Tax (5%)</span>
                                    <span>{{ $currency }}{{ number_format($totals['taxTotal'], 0) }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-lg font-black text-foreground">
                                <span>Total Queue</span>
                                <span class="text-indigo-600">{{ $currency }}{{ number_format($totals['grandTotal'], 0) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button wire:click="setView('menu')" class="size-14 bg-secondary border border-border rounded-xl flex items-center justify-center text-muted-foreground hover:text-indigo-500 hover:bg-secondary/80 transition-all shrink-0">
                                <i data-lucide="plus" class="size-6"></i>
                            </button>

                            <button wire:click="submitOrder" class="h-14 flex-1 bg-indigo-600 hover:bg-indigo-500 rounded-xl flex items-center justify-center gap-3 text-white font-black uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-indigo-600/20 active:scale-[0.98] transition-all border border-border">
                                <i data-lucide="send" class="size-4"></i>
                                Confirm & Send to Kitchen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($view === 'alerts')
            <div class="max-w-2xl mx-auto w-full p-8 space-y-4">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-black">Notifications</h2>
                    @if($this->notifications->isNotEmpty())
                        <button wire:click="clearAllNotifications" class="text-[10px] font-black uppercase tracking-widest text-indigo-500 hover:text-indigo-400 transition-colors">
                            Clear All
                        </button>
                    @endif
                </div>
                @forelse($this->notifications as $notification)
                    <div class="p-5 rounded-xl bg-card border border-border flex items-start gap-4 {{ $notification->read ? 'opacity-50' : '' }}">
                        <div class="size-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0">
                            <i data-lucide="bell" class="size-5"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-[10px] font-black text-zinc-600 dark:text-zinc-500 uppercase tracking-widest">{{ $notification->created_at->diffForHumans() }}</span>
                                @if(!$notification->read)
                                    <div class="size-2 bg-indigo-500 rounded-full shadow-sm shadow-indigo-500/50"></div>
                                @endif
                            </div>
                            <p class="text-sm font-black text-zinc-900 dark:text-zinc-100 leading-relaxed">{{ $notification->message }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-20 text-center text-zinc-600">
                        <i data-lucide="bell-off" class="size-12 mx-auto mb-4 opacity-20"></i>
                        <p class="text-xs font-bold uppercase tracking-widest">No notifications yet</p>
                    </div>
                @endforelse
            </div>

        @elseif($view === 'bill')
            <div class="max-w-4xl mx-auto w-full pb-64">
                @php
                    $order = $this->currentOrder;
                    $allItems = $order ? $order->orderItems : collect();
                @endphp

                @if($order)
                    <div class="flex flex-col gap-8">
                        <!-- Top Detail Card -->
                        <div class="bg-card border border-border rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-600/5 blur-3xl rounded-full"></div>
                            
                            <div class="flex items-start justify-between relative z-10">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-3xl font-black tracking-tight text-foreground uppercase font-mono">Table {{ $order->table_label }}</h3>
                                        <span class="px-3 py-1 bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 rounded-full text-[8px] font-black tracking-widest uppercase">{{ $order->is_paid ? 'Paid' : 'Open Order' }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Order #{{ $order->order_number }}</span>
                                        <span class="size-1 rounded-full bg-muted-foreground/50"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-400">Started {{ $order->created_at->format('g:i A') }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Primary Waiter</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-black text-foreground">{{ $order->creator->name ?? 'N/A' }}</span>
                                        <div class="size-6 rounded-full bg-secondary flex items-center justify-center text-[10px] font-black border border-border uppercase">{{ substr($order->creator->name ?? 'N', 0, 1) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Breakdown Grouped by Status -->
                        <div class="flex flex-col gap-6">
                            @foreach(['ready' => 'Pick Up Ready', 'preparing' => 'In Kitchen', 'sent' => 'Sent to Kitchen', 'served' => 'Served History'] as $status => $label)
                                @php 
                                    $groupedItems = $allItems->filter(function($i) use ($status) {
                                        return ($i->status instanceof \App\Enums\OrderStatus ? $i->status->value : $i->status) === $status;
                                    });
                                @endphp
                                @if($groupedItems->isNotEmpty())
                                    <div class="flex flex-col gap-3">
                                        <div class="flex items-center justify-between px-2">
                                            <h4 @class([
                                                'text-[10px] font-black uppercase tracking-widest',
                                                'text-emerald-700 dark:text-emerald-400' => $status === 'ready',
                                                'text-blue-700 dark:text-blue-400 animate-pulse' => $status === 'preparing',
                                                'text-muted-foreground' => !in_array($status, ['ready', 'preparing'])
                                            ])>
                                                {{ $label }}
                                                @if($status === 'preparing')
                                                    <span class="ml-2 lowercase font-bold italic tracking-normal">(Cooking now...)</span>
                                                @endif
                                            </h4>
                                            <span class="text-[9px] font-black text-muted-foreground font-mono">{{ $groupedItems->count() }} Items</span>
                                        </div>

                                        <div class="grid grid-cols-1 gap-2.5">
                                            @foreach($groupedItems as $item)
                                                <div class="bg-card border border-border rounded-2xl p-5 flex items-center justify-between group hover:border-indigo-500/30 transition-all">
                                                    <div class="flex items-center gap-5">
                                                            <div @class([
                                                                'size-14 rounded-xl border flex items-center justify-center shrink-0 transition-all relative overflow-hidden',
                                                                'bg-emerald-500/10 border-emerald-500/20' => $status === 'ready',
                                                                'bg-blue-500/10 border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.2)]' => $status === 'preparing',
                                                                'bg-secondary border-border' => !in_array($status, ['ready', 'preparing'])
                                                            ])>
                                                                @php
                                                                    $imgUrl = $item->menuItem->thumbnail_url;
                                                                    $emoji = $item->menuItem->categoryInfo->emoji ?? '🍴';
                                                                @endphp

                                                                @if($imgUrl)
                                                                    <img src="{{ $imgUrl }}" alt="{{ $item->menuItem->name }}" class="w-full h-full object-cover">
                                                                @elseif($status === 'preparing')
                                                                    <div class="absolute inset-0 bg-blue-500/5 animate-pulse"></div>
                                                                    <i data-lucide="flame" class="size-6 text-blue-500 animate-bounce relative z-10"></i>
                                                                @else
                                                                    <i data-lucide="{{ $status === 'ready' ? 'check' : 'utensils' }}" @class([
                                                                        'size-6',
                                                                        'text-emerald-500' => $status === 'ready',
                                                                        'text-muted-foreground/40' => !in_array($status, ['ready', 'preparing'])
                                                                    ])></i>
                                                                @endif

                                                                @if($status === 'preparing' && $imgUrl)
                                                                    <div class="absolute inset-0 bg-blue-500/20 animate-pulse border-2 border-blue-500 rounded-xl z-20"></div>
                                                                @endif

                                                            <div class="absolute -top-1 -right-1 size-3 bg-white rounded-sm flex items-center justify-center border border-zinc-200">
                                                                @if($item->menuItem->is_veg)
                                                                    <div class="border border-green-600 p-[0.5px] rounded-[1px]"><div class="size-1 bg-green-600 rounded-full"></div></div>
                                                                @else
                                                                    <div class="border border-rose-600 p-[0.5px] rounded-[1px]"><div class="size-1 bg-rose-600 rounded-full"></div></div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <h5 class="text-sm font-black text-foreground">{{ $item->menuItem->name }}</h5>
                                                            <div class="flex items-center gap-2 mt-0.5">
                                                                <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest font-mono">QTY: {{ $item->quantity }}</span>
                                                                @if($item->kot_id)
                                                                    <span class="size-1 rounded-full bg-zinc-800"></span>
                                                                    <span class="text-[9px] font-bold text-zinc-600 uppercase tracking-widest">KOT BATCH #{{ $item->kot->batch_number }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-4">
                                                        @if($item->status->value === 'ready')
                                                            <button 
                                                                wire:click="markItemAsServed('{{ $item->id }}')"
                                                                class="h-9 px-5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-emerald-600/10"
                                                            >
                                                                <i data-lucide="check" class="size-3.5"></i>
                                                                Serve
                                                            </button>
                                                        @elseif($item->status->value === 'preparing')
                                                            <div class="h-9 px-4 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-xl flex items-center gap-2 text-[10px] font-black uppercase tracking-widest animate-pulse">
                                                                <i data-lucide="flame" class="size-3.5"></i>
                                                                Cooking Now
                                                            </div>
                                                        @elseif($item->status->value === 'sent' || $item->status->value === 'pending')
                                                            <div class="h-9 px-4 bg-zinc-500/10 text-zinc-500 border border-zinc-500/20 rounded-xl flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                                                                <i data-lucide="clock" class="size-3.5"></i>
                                                                In Queue
                                                            </div>
                                                        @elseif($item->status->value === 'served')
                                                            <div class="h-9 px-4 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-xl flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                                                                <i data-lucide="check-circle" class="size-3.5"></i>
                                                                Item Served
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Bill Summary -->
                        <div class="bg-card border border-border rounded-3xl p-8 mt-4 space-y-6 shadow-2xl">
                            <div class="grid grid-cols-2 gap-8 border-b border-border pb-8 relative">
                                <div class="space-y-3">
                                    <div class="flex justify-between text-[11px] font-bold text-muted-foreground uppercase tracking-widest">
                                        <span>Items Subtotal</span>
                                        <span class="text-foreground font-mono">{{ $currency }}{{ number_format($totals['subtotal'], 2) }}</span>
                                    </div>
                                    @if($totals['serviceCharge'] > 0)
                                        <div class="flex justify-between text-[11px] font-bold text-muted-foreground uppercase tracking-widest font-mono">
                                            <span>Service Charge</span>
                                            <span class="text-foreground">{{ $currency }}{{ number_format($totals['serviceCharge'], 2) }}</span>
                                        </div>
                                    @endif
                                    @if($taxEnabled)
                                        <div class="flex justify-between text-[11px] font-bold text-muted-foreground uppercase tracking-widest font-mono">
                                            <span>GST ({{ (int)$taxPercent }}%)</span>
                                            <span class="text-foreground">{{ $currency }}{{ number_format($totals['taxTotal'], 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-3 font-mono">
                                    @if($totals['alreadyPaid'] > 0)
                                        <div class="flex justify-between text-[11px] font-bold text-muted-foreground uppercase tracking-widest">
                                            <span>Already Paid</span>
                                            <span class="text-emerald-700 dark:text-emerald-400">{{ $currency }}{{ number_format($totals['alreadyPaid'], 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between text-[11px] font-bold {{ $totals['remainingDue'] < 0.01 ? 'text-muted-foreground opacity-70' : 'text-rose-700 dark:text-rose-400' }} uppercase tracking-widest">
                                        <span>Remaining Due</span>
                                        <span class="{{ $totals['remainingDue'] < 0.01 ? 'text-muted-foreground' : 'text-rose-700 dark:text-rose-400' }}">{{ $currency }}{{ number_format($totals['remainingDue'], 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Discount Controls -->
                            <div class="space-y-4 pt-2 border-t border-border">
                                <div class="flex items-center justify-between">
                                    <button 
                                        wire:click="$toggle('showDiscountRow')"
                                        class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest transition-colors px-3 py-1.5 rounded-lg {{ $showDiscountRow ? 'bg-indigo-600 text-white' : 'bg-muted text-muted-foreground hover:text-foreground' }}"
                                    >
                                        <i data-lucide="tag" class="size-3.5"></i>
                                        {{ $showDiscountRow ? 'Hide Discount' : 'Add Discount' }}
                                    </button>
                                    
                                    @if($totals['discountTotal'] > 0)
                                        <div class="flex items-center gap-2 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest font-mono">
                                            <span>Applied Discount</span>
                                            <span>- {{ $currency }}{{ number_format($totals['discountTotal'], 2) }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($showDiscountRow)
                                    <div x-data="{ customValue: @entangle('discountValue') }" class="space-y-3 p-4 rounded-2xl bg-muted/30 border border-border animate-in fade-in slide-in-from-top-2">
                                        <!-- Presets -->
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($this->discountPresets as $preset)
                                                <button 
                                                    wire:click="$set('discountValue', {{ $preset }}); $set('discountType', 'percentage')"
                                                    class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $discountValue == $preset && $discountType === 'percentage' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'bg-muted text-muted-foreground hover:bg-muted/70' }}"
                                                >
                                                    {{ $preset }}%
                                                </button>
                                            @endforeach
                                        </div>

                                        <!-- Custom Input -->
                                        <div class="flex items-center gap-3">
                                            <div class="relative flex-1 group">
                                                <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none text-muted-foreground font-mono text-sm group-focus-within:text-indigo-500 transition-colors">
                                                    {{ $discountType === 'percentage' ? '%' : $currency }}
                                                </div>
                                                <input 
                                                    type="number" 
                                                    wire:model.live.debounce.300ms="discountValue"
                                                    placeholder="Custom Value"
                                                    class="w-full bg-background border border-border rounded-xl pl-10 pr-4 h-12 text-sm font-mono text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all placeholder:text-muted-foreground/50"
                                                />
                                            </div>
                                            <div class="bg-background p-1.5 rounded-xl border border-border flex gap-1 items-center">
                                                <button 
                                                    wire:click="$set('discountType', 'percentage')"
                                                    class="px-3 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all {{ $discountType === 'percentage' ? 'bg-indigo-600 text-white' : 'text-muted-foreground hover:text-foreground' }}"
                                                >
                                                    %
                                                </button>
                                                <button 
                                                    wire:click="$set('discountType', 'fixed')"
                                                    class="px-3 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all {{ $discountType === 'fixed' ? 'bg-indigo-600 text-white' : 'text-muted-foreground hover:text-foreground' }}"
                                                >
                                                    {{ $currency }}
                                                </button>
                                            </div>
                                            @if($discountValue > 0)
                                                <button 
                                                    wire:click="$set('discountValue', 0)"
                                                    class="p-3 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white transition-all border border-rose-500/20"
                                                >
                                                    <i data-lucide="x" class="size-4"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex items-center justify-between pt-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground mb-1">Session Grand Total</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-black text-foreground font-mono">{{ $currency }}{{ number_format($totals['grandTotal'], 2) }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-1">
                                    <div class="px-4 py-1.5 rounded-full bg-muted border border-border text-[9px] font-black uppercase tracking-widest text-muted-foreground">
                                        Payment Method: {{ strtoupper($paymentMethod) }}
                                    </div>
                                    <button 
                                        wire:click="printBill" 
                                        wire:loading.attr="disabled"
                                        class="h-14 px-8 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-500 rounded-2xl flex items-center justify-center gap-3 text-[10px] font-black uppercase tracking-widest transition-all border border-indigo-500/10 active:scale-95 disabled:opacity-50"
                                    >
                                        <i data-lucide="printer" class="size-5" wire:loading.remove wire:target="printBill"></i>
                                        <div wire:loading wire:target="printBill" class="size-5 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                                        Print Bill
                                    </button>

                                    <button wire:click="printBill" class="text-[9px] font-black uppercase tracking-widest text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1.5 mt-2 opacity-50">
                                        <i data-lucide="download" class="size-3"></i>
                                        Extract PDF Invoice
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Global Billing Actions (Sticky Bottom) -->
                        <div class="fixed bottom-20 left-0 right-0 z-30 bg-background/80 backdrop-blur-xl border-t border-border px-8 py-6 shadow-[0_-10px_40px_rgba(0,0,0,0.4)]">
                            <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4">
                                @if($totals['remainingDue'] > 0.05)
                                    <button 
                                        wire:click="initiatePayment"
                                        wire:loading.attr="disabled"
                                        class="h-14 col-span-2 bg-emerald-600 hover:bg-emerald-500 rounded-2xl flex items-center justify-center gap-3 text-white text-[10px] font-black uppercase tracking-widest transition-all shadow-[0_10px_30px_rgba(16,185,129,0.2)] active:scale-95 border border-white/10 disabled:opacity-50"
                                    >
                                        <i data-lucide="check-circle" class="size-5" wire:loading.remove></i>
                                        <div wire:loading class="size-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                        Mark as Paid ({{ $currency }}{{ number_format($totals['remainingDue'], 2) }})
                                    </button>
                                @else
                                    <div class="h-14 col-span-2 relative">
                                        <button 
                                            wire:click="freeTables"
                                            wire:loading.attr="disabled"
                                            @disabled(!$this->canCheckout)
                                            @class([
                                                'w-full h-full rounded-2xl flex items-center justify-center gap-3 text-white text-[10px] font-black uppercase tracking-widest transition-all border border-white/10 shadow-lg disabled:opacity-50',
                                                'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-600/20 active:scale-95' => $this->canCheckout,
                                                'bg-zinc-800 text-zinc-500 cursor-not-allowed opacity-80' => !$this->canCheckout
                                            ])
                                        >
                                            <i data-lucide="{{ $this->canCheckout ? 'log-out' : 'lock' }}" class="size-5" wire:loading.remove></i>
                                            <div wire:loading class="size-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                            {{ $this->canCheckout ? 'Checkout & Free Tables' : 'Serve all items to Checkout' }}
                                        </button>
                                        
                                        @if(!$this->canCheckout)
                                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 text-white text-[9px] font-black uppercase tracking-widest rounded-xl shadow-xl z-20 border border-white/10 whitespace-nowrap animate-pulse">
                                                <div class="size-1.5 bg-white rounded-full"></div>
                                                {{ $this->pendingItemsCount }} Items Pending
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <button 
                                    wire:click="addMoreItems"
                                    wire:loading.attr="disabled"
                                    @class([
                                        'h-14 bg-secondary hover:bg-secondary/80 border border-border rounded-xl flex items-center justify-center gap-3 text-foreground text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50',
                                        'col-span-2' => !$this->canServeAnyReady
                                    ])
                                >
                                    <i data-lucide="plus" class="size-4 text-indigo-500" wire:loading.remove></i>
                                    <div wire:loading class="size-4 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                                    Add More
                                </button>

                                @if($this->canServeAnyReady)
                                    <button 
                                        @click="$dispatch('open-modal', 'mark-all-served')"
                                        wire:loading.attr="disabled"
                                        class="h-14 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-500 rounded-xl flex items-center justify-center gap-3 text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-500/10"
                                    >
                                        <i data-lucide="check-square" class="size-4" wire:loading.remove></i>
                                        <div wire:loading class="size-4 border-2 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin"></div>
                                        Serve All Ready
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-32 text-zinc-500 border-2 border-dashed border-white/5 rounded-3xl">
                        <i data-lucide="inbox" class="size-16 mb-4 opacity-10 font-mono">SESSION_NULL</i>
                        <p class="font-bold uppercase tracking-widest">No open order selected</p>
                        <button wire:click="setView('home')" class="mt-8 px-8 py-3 bg-indigo-600 rounded-full text-white text-[10px] font-black uppercase tracking-widest">Return to Overview</button>
                    </div>
                @endif
            </div>

        @elseif($view === 'profile')
            <div class="max-w-4xl mx-auto w-full p-6 space-y-8 pb-32">
                <!-- User Identity Card -->
                <div class="bg-card/50 backdrop-blur-xl rounded-3xl border border-border p-8 flex items-center gap-6 shadow-2xl">
                    <div class="size-20 rounded-full bg-indigo-600 flex items-center justify-center text-2xl font-black text-white shadow-lg shadow-indigo-600/20">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-black tracking-tight text-foreground">{{ $user->name }}</h3>
                        <p class="text-sm font-bold text-zinc-500 lowercase">{{ $user->email }}</p>
                        <div class="mt-2 text-[9px] font-black uppercase tracking-[0.2em] text-indigo-400 px-2 py-0.5 rounded bg-indigo-400/10 inline-block">Waiter</div>
                    </div>
                </div>

                <!-- Settings Group -->
                <div class="space-y-4">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 ml-2">Settings</h4>
                    <div class="bg-card/50 rounded-3xl border border-border divide-y divide-border overflow-hidden">
                        <!-- Notifications -->
                        <div class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                                    <i data-lucide="bell" class="size-5"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-foreground">Notifications</p>
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest mt-0.5">Receive order updates</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" wire:click="toggleNotifications" @if(Auth::user()->notifications_enabled) checked @endif>
                                <div class="w-11 h-6 bg-secondary peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <div class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                                    <i data-lucide="moon" class="size-5"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-foreground">Dark Mode</p>
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest mt-0.5">Toggle dark theme</p>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                @click="$store.theme.toggle()"
                                role="switch"
                                :aria-checked="$store.theme.current === 'dark'"
                                aria-label="Dark mode"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none z-10"
                                :class="$store.theme.current === 'dark' ? 'bg-indigo-600' : 'bg-muted'"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out"
                                    :class="$store.theme.current === 'dark' ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Support Group -->
                <div class="space-y-4">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 ml-2">Support</h4>
                    <div class="bg-card/50 rounded-3xl border border-border divide-y divide-border overflow-hidden">
                        <div class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-secondary text-foreground flex items-center justify-center">
                                    <i data-lucide="help-circle" class="size-5"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-foreground">Help & Support</p>
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest mt-0.5">Contact center</p>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="size-5 text-zinc-700"></i>
                        </div>
                        <div class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-secondary text-foreground flex items-center justify-center">
                                    <i data-lucide="settings" class="size-5"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-foreground">Account Settings</p>
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest mt-0.5">Manage your account</p>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="size-5 text-zinc-700"></i>
                        </div>
                    </div>
                </div>

                <div class="text-center py-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Version 1.0.0</p>
                    <p class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground mt-1">Restaurant POS System</p>
                </div>

                <button 
                    wire:click="logout" 
                    class="w-full py-5 rounded-2xl bg-destructive/10 hover:bg-destructive/20 text-destructive text-[11px] font-black uppercase tracking-[0.3em] flex items-center justify-center gap-3 transition-all active:scale-[0.98] border border-destructive/20"
                >
                    <i data-lucide="power" class="size-5"></i>
                    Terminate Session
                </button>
            </div>
        @endif
        </div>
    </main>

    <!-- Mark All Served Confirmation Modal -->
    <div 
        x-data="{ show: false }"
        @open-modal.window="if($event.detail === 'mark-all-served') show = true"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80"
    >
        <div @click.away="show = false" class="w-full max-w-sm bg-card border border-border rounded-3xl p-8 shadow-2xl">
            <h4 class="text-lg font-black text-foreground mb-2">Mark all Served</h4>
            <p class="text-sm text-muted-foreground font-bold mb-8">Are you sure you want to mark the entire order as served?</p>
            
            <div class="grid grid-cols-2 gap-4">
                <button @click="show = false" class="h-12 bg-secondary hover:bg-secondary/80 rounded-xl text-xs font-black uppercase tracking-widest text-muted-foreground transition-all">
                    Cancel
                </button>
                <button wire:click="markAllAsServed" @click="show = false" class="h-12 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-black uppercase tracking-widest text-white transition-all shadow-lg">
                    Continue
                </button>
            </div>
        </div>
    </div>

    

    <!-- Payment Selection Modal -->
    <div 
        x-show="$wire.showPaymentModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/9 bg-card/10 backdrop-blur-md"
        x-cloak
    >
        <div class="w-full max-w-sm bg-card border border-border rounded-[2.5rem] p-8 shadow-2xl" @click.away="$wire.showPaymentModal = false">
            <div class="text-center mb-8">
                <h4 class="text-2xl font-black text-foreground mb-2">Settle Payment</h4>
                <p class="text-xs text-muted-foreground font-bold uppercase tracking-widest">Select payment method for {{ $currency }}{{ number_format($totals['remainingDue'], 0) }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                @foreach($this->enabledPaymentMethods as $method)
                    <button 
                        wire:click="markAsPaid('{{ $method }}')"
                        wire:loading.attr="disabled"
                        class="h-16 px-6 rounded-2xl bg-secondary hover:bg-indigo-600 group transition-all flex items-center justify-between border border-border/50 disabled:opacity-50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="size-10 rounded-xl bg-card flex items-center justify-center group-hover:bg-white/10 transition-colors">
                                <i data-lucide="{{ $method === 'cash' ? 'banknote' : ($method === 'card' ? 'credit-card' : 'smartphone') }}" class="size-5 text-indigo-500 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="text-sm font-black uppercase tracking-widest group-hover:text-white transition-colors">{{ strtoupper($method) }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="size-4 text-muted-foreground group-hover:text-white group-hover:translate-x-1 transition-all"></i>
                    </button>
                @endforeach
            </div>

            <button @click="$wire.showPaymentModal = false" class="w-full mt-6 py-4 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground hover:text-foreground transition-colors">
                Cancel
            </button>
        </div>
    </div>

    <!-- Global Floating Action Island (Review Cart) -->
    @if(count($cart) > 0 && $view === 'menu')
        <div class="fixed bottom-36 left-1/2 -translate-x-1/2 w-[calc(100%-3rem)] max-w-2xl z-40">
            <div class="h-16 bg-card border border-border rounded-3xl flex items-center justify-between px-8 text-foreground shadow-2xl group transition-all animate-in slide-in-from-bottom-5">
                <div class="flex items-center gap-4">
                    <div class="size-11 rounded-2xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20">
                        <i data-lucide="shopping-bag" class="size-5 text-indigo-600"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-black tracking-tight uppercase">{{ count($cart) }} Items Ready</span>
                        <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-muted-foreground font-mono">{{ $currency }}{{ number_format($totals['grandTotal'], 0) }}</span>
                    </div>
                </div>
                <button wire:click="setView('summary')" class="h-11 px-8 bg-indigo-600 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest transition-all hover:bg-indigo-500 shadow-lg shadow-indigo-600/20 active:scale-95">
                    Review Order
                </button>
            </div>
        </div>
    @endif

    <!-- Bottom Navigation: Refined Premium Style -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-card/80 backdrop-blur-md border-t border-border h-20 px-6 flex items-center justify-around pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.02)]">
        <button 
            wire:key="nav-home"
            wire:click="setView('home')"
            class="flex flex-col items-center gap-1.5 transition-all group relative {{ $view === 'home' || $view === 'menu' || $view === 'summary' || $view === 'bill' ? 'text-indigo-600' : 'text-muted-foreground' }}"
        >
            <div class="p-2.5 rounded-2xl transition-all {{ $view === 'home' || $view === 'menu' || $view === 'summary' || $view === 'bill' ? 'bg-indigo-600/10' : 'group-hover:bg-muted' }}">
                <i data-lucide="layout-grid" class="w-6 h-6 pointer-events-none"></i>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest">Home</span>
            @if($view === 'home' || $view === 'menu' || $view === 'summary' || $view === 'bill')
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 rounded-full"></div>
            @endif
        </button>

        <button 
            wire:key="nav-alerts"
            wire:click="setView('alerts')"
            class="flex flex-col items-center gap-1.5 transition-all group relative {{ $view === 'alerts' ? 'text-indigo-600' : 'text-muted-foreground' }}"
        >
            <div class="p-2.5 rounded-2xl transition-all {{ $view === 'alerts' ? 'bg-indigo-600/10' : 'group-hover:bg-muted' }}">
                <i data-lucide="bell" class="w-6 h-6 pointer-events-none"></i>
                @if($this->unreadCount > 0)
                    <span class="absolute top-2 right-2 w-3 h-3 bg-rose-500 rounded-full ring-4 ring-card"></span>
                @endif
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest">Alerts</span>
            @if($view === 'alerts')
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 rounded-full"></div>
            @endif
        </button>

        <button 
            wire:key="nav-profile"
            wire:click="setView('profile')"
            class="flex flex-col items-center gap-1.5 transition-all group relative {{ $view === 'profile' ? 'text-indigo-600' : 'text-muted-foreground' }}"
        >
            <div class="p-2.5 rounded-2xl transition-all {{ $view === 'profile' ? 'bg-indigo-600/10' : 'group-hover:bg-muted' }}">
                <i data-lucide="user" class="w-6 h-6 pointer-events-none"></i>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest">Profile</span>
            @if($view === 'profile')
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-indigo-600 rounded-full"></div>
            @endif
        </button>
    </nav>
    <!-- Receipt Preview Modal (works without a physical printer) -->
    <div
        x-data="{ open: false }"
        x-on:show-receipt-preview.window="open = true"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[120] flex items-center justify-center bg-background/80 backdrop-blur-sm p-6 print:hidden"
        x-transition:enter="transition duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh]" @click.away="open = false">
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <h3 class="text-sm font-black uppercase tracking-widest text-foreground">Receipt Preview</h3>
                <button @click="open = false" aria-label="Close preview" class="size-9 rounded-xl bg-muted/50 hover:bg-muted flex items-center justify-center text-muted-foreground">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 bg-zinc-100 dark:bg-zinc-900 flex justify-center receipt-preview-wrap">
                @include('livewire.waiter.partials.bill-receipt')
            </div>
            <div class="p-4 border-t border-border flex gap-3">
                <button
                    @click="open = false; printThermalReceipt()"
                    class="flex-1 h-12 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest transition-all"
                >
                    <i data-lucide="printer" class="size-4"></i>
                    Print / Save as PDF
                </button>
                <button @click="open = false" class="h-12 px-6 bg-muted hover:bg-muted/70 text-foreground rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Print Logic & Styles -->
    <style>
        /* On-screen receipt preview: override the receipt's default hidden state */
        .receipt-preview-wrap #printable-receipt {
            display: block;
            zoom: 1.5;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        @media print {
            /* 1. Force Page Size & Margins - CRITICAL for Thermal Printers */
            @page {
                size: 80mm auto !important;
                margin: 0 !important;
            }

            /* 2. Global Reset - Force auto-height based on content */
            html, body {
                width: 80mm !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                overflow: visible !important;
                display: block !important;
            }

            /* 3. Aggressive UI Hide */
            body > *:not(#printable-receipt) {
                display: none !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            /* 4. Natural Flow Layout - This ensures body height = receipt height */
            body > #printable-receipt {
                display: block !important;
                visibility: visible !important;
                position: relative !important; /* Allow natural flow */
                width: 80mm !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 4mm !important;
                box-sizing: border-box !important;
                background: white !important;
            }

            /* 5. Ensure all text is crisp black */
            #printable-receipt * {
                color: black !important;
                background: transparent !important;
                visibility: visible !important;
                page-break-inside: avoid !important;
            }
        }
    </style>

    @script
    <script>
    const alertSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        
        $wire.on('view-changed', () => {
            window.scrollTo(0, 0);
        });
        
        $wire.on('play-alert', () => {
            alertSound.play().catch(e => console.log('Audio play failed:', e));
        });

        window.printThermalReceipt = () => {
            const receipt = document.getElementById('printable-receipt');
            if (!receipt) return;

            const originalParent = receipt.parentElement;
            const originalNextSibling = receipt.nextSibling;

            // Move to body to bypass layout constraints (print CSS hides everything else)
            document.body.appendChild(receipt);

            // Small delay to ensure browser acknowledges the move before printing.
            // Without a physical printer the browser dialog still offers "Save as PDF".
            setTimeout(() => {
                window.print();

                if (originalNextSibling) {
                    originalParent.insertBefore(receipt, originalNextSibling);
                } else {
                    originalParent.appendChild(receipt);
                }
            }, 100);
        };

        $wire.on('trigger-print-bill', () => window.printThermalReceipt());

        $wire.on('notify', () => {});
    </script>
    @endscript
</div>
