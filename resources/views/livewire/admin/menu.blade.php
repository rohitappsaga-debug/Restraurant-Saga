<div class="space-y-8" x-data="{ 
    showAddDialog: @entangle('showAddDialog'), 
    editingItemId: @entangle('editingItemId'),
    is_veg: @entangle('is_veg'),
    available: @entangle('available')
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Menu Management</h1>
            <p class="text-muted-foreground mt-2 text-lg font-medium">Manage your culinary offerings and pricing</p>
        </div>
        <div class="flex items-center gap-4">
            <button 
                @click="$wire.openCreate()"
                class="h-12 px-8 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white shadow-lg shadow-orange-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] border-0 rounded-2xl font-bold flex items-center gap-3"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                Add New Item
            </button>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Items -->
        <div class="bg-card p-7 rounded-[2.5rem] border border-border shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity pointer-events-none rotate-12">
                <i data-lucide="utensils" class="size-32 text-blue-600"></i>
            </div>
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-14 bg-blue-500/10 rounded-2xl flex items-center justify-center border border-blue-500/20">
                    <i data-lucide="utensils" class="size-7 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-1">Total Items</p>
                    <h3 class="text-3xl font-black text-foreground leading-none">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>

        <!-- Available -->
        <div class="bg-card p-7 rounded-[2.5rem] border border-border shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity pointer-events-none rotate-12">
                <i data-lucide="check-circle" class="size-32 text-emerald-600"></i>
            </div>
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/20">
                    <i data-lucide="check-circle" class="size-7 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-1">Available</p>
                    <h3 class="text-3xl font-black text-foreground leading-none">{{ $stats['available'] }}</h3>
                </div>
            </div>
        </div>

        <!-- Unavailable -->
        <div class="bg-card p-7 rounded-[2.5rem] border border-border shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity pointer-events-none rotate-12">
                <i data-lucide="ban" class="size-32 text-rose-600"></i>
            </div>
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-14 bg-rose-500/10 rounded-2xl flex items-center justify-center border border-rose-500/20">
                    <i data-lucide="ban" class="size-7 text-rose-600"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-1">Unavailable</p>
                    <h3 class="text-3xl font-black text-foreground leading-none">{{ $stats['unavailable'] }}</h3>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-card p-7 rounded-[2.5rem] border border-border shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity pointer-events-none rotate-12">
                <i data-lucide="sandwich" class="size-32 text-amber-600"></i>
            </div>
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-14 bg-amber-500/10 rounded-2xl flex items-center justify-center border border-amber-500/20">
                    <i data-lucide="sandwich" class="size-7 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-1">Categories</p>
                    <h3 class="text-3xl font-black text-foreground leading-none">{{ $stats['categories'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-card border border-border shadow-sm rounded-[3rem] overflow-hidden">
        <!-- Filters Toolbar -->
        <div class="p-8 border-b border-border bg-muted/20">
            <div class="flex flex-col lg:flex-row gap-6 justify-between items-center">
                <div class="relative w-full lg:max-w-md group">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-primary transition-colors"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search menu items..."
                        class="pl-12 h-14 bg-background border border-border focus:border-primary focus:ring-4 focus:ring-primary/10 focus:outline-none transition-all w-full rounded-[1.25rem] text-sm font-semibold placeholder:text-muted-foreground shadow-sm"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                    <button class="flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-xl shadow-sm text-sm font-bold text-foreground/70 hover:bg-muted transition-colors">
                        <i data-lucide="filter" class="size-4 opacity-70"></i>
                        Filters
                    </button>
                    
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="h-10 px-4 rounded-xl bg-card border border-border flex items-center justify-between gap-3 text-sm font-bold text-foreground/80 shadow-sm transition-all hover:bg-muted outline-none"
                        >
                            <span class="truncate">
                                {{ [
                                    'all' => 'All Status',
                                    'available' => 'Available',
                                    'unavailable' => 'Unavailable'
                                ][$filterStatus] ?? 'Status' }}
                            </span>
                            <i data-lucide="chevron-down" class="size-3.5 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute top-full mt-2 left-0 w-full min-w-[140px] bg-card border border-border shadow-2xl rounded-xl overflow-hidden z-[50] backdrop-blur-xl"
                            x-cloak
                        >
                            @foreach(['all' => 'All Status', 'available' => 'Available', 'unavailable' => 'Unavailable'] as $val => $label)
                                <button 
                                    @click="$wire.set('filterStatus', '{{ $val }}'); open = false"
                                    class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-primary/10 transition-colors {{ $filterStatus === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="h-10 px-4 rounded-xl bg-card border border-border flex items-center justify-between gap-3 text-sm font-bold text-foreground/80 shadow-sm transition-all hover:bg-muted outline-none"
                        >
                            <span class="truncate">
                                {{ [
                                    'all' => 'All Types',
                                    'veg' => 'Veg Only',
                                    'non-veg' => 'Non-Veg Only'
                                ][$filterType] ?? 'Types' }}
                            </span>
                            <i data-lucide="chevron-down" class="size-3.5 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute top-full mt-2 left-0 w-full min-w-[140px] bg-card border border-border shadow-2xl rounded-xl overflow-hidden z-[50] backdrop-blur-xl"
                            x-cloak
                        >
                            @foreach(['all' => 'All Types', 'veg' => 'Veg Only', 'non-veg' => 'Non-Veg Only'] as $val => $label)
                                <button 
                                    @click="$wire.set('filterType', '{{ $val }}'); open = false"
                                    class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-primary/10 transition-colors {{ $filterType === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="h-10 px-4 rounded-xl bg-card border border-border flex items-center justify-between gap-3 text-sm font-bold text-foreground/80 shadow-sm transition-all hover:bg-muted outline-none min-w-[140px]"
                        >
                            <span class="truncate">
                                {{ $selectedCategory === 'All' ? 'All Categories' : ($categories->find($selectedCategory)?->name ?? 'Categories') }}
                            </span>
                            <i data-lucide="chevron-down" class="size-3.5 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute top-full mt-2 right-0 w-full min-w-[180px] bg-card border border-border shadow-2xl rounded-xl overflow-hidden z-[50] backdrop-blur-xl"
                            x-cloak
                        >
                            <button 
                                @click="$wire.set('selectedCategory', 'All'); open = false"
                                class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-primary/10 transition-colors {{ $selectedCategory === 'All' ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                            >
                                All Categories
                            </button>
                            @foreach($categories as $category)
                                <button 
                                    @click="$wire.set('selectedCategory', '{{ $category->id }}'); open = false"
                                    class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-primary/10 transition-colors {{ $selectedCategory === $category->id ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                                >
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto overflow-y-hidden no-scrollbar">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-muted/50 border-b border-border">
                        <th class="py-6 px-8 text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Item Details</th>
                        <th class="py-6 px-8 text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Category</th>
                        <th class="py-6 px-8 text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Price</th>
                        <th class="py-6 px-8 text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Attributes</th>
                        <th class="py-6 px-8 text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Status</th>
                        <th class="py-6 px-8 text-right text-[11px] font-black text-muted-foreground uppercase tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($items as $item)
                        <tr class="group hover:bg-muted/30 transition-all">
                            <td class="py-6 px-8">
                                <div class="flex items-center gap-5">
                                    <div class="size-14 rounded-[1.25rem] bg-muted border border-border/50 flex items-center justify-center flex-shrink-0 overflow-hidden group-hover:scale-105 transition-transform relative">
                                        @if($item->image)
                                            <img src="{{ Storage::url($item->image) . '?t=' . ($item->updated_at?->timestamp ?? time()) }}" 
                                                 class="w-full h-full object-cover"
                                                 loading="lazy">
                                            @if(in_array($item->id, $generatingIds))
                                                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center">
                                                    <i data-lucide="refresh-cw" class="size-5 text-white animate-spin"></i>
                                                </div>
                                            @endif
                                        @else
                                            <div class="size-14 bg-gradient-to-br from-primary/5 to-muted flex items-center justify-center text-primary font-extrabold text-xl">
                                                {{ strtoupper(substr($item->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <div class="font-bold text-foreground text-lg tracking-tight group-hover:text-primary transition-colors">{{ $item->name }}</div>
                                        <div class="text-xs text-muted-foreground font-medium line-clamp-1 max-w-[280px]">{{ $item->description }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-8">
                                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[11px] font-bold text-muted-foreground bg-muted border border-border/50 tracking-wide uppercase">
                                    {{ $item->categoryInfo?->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="py-6 px-8">
                                <div class="text-lg font-black text-foreground tracking-tight">{{ $settings['currency'] }}{{ number_format($item->price, 2) }}</div>
                            </td>
                            <td class="py-6 px-8">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-[10px] font-bold uppercase tracking-wider">
                                        <i data-lucide="clock" class="size-3.5 opacity-60"></i> {{ $item->preparation_time }}m
                                    </div>
                                    @if($item->is_veg)
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-500 text-[9px] font-black uppercase border border-emerald-500/20 w-fit">
                                            <i data-lucide="leaf" class="size-3"></i> Veg
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-500/15 text-rose-500 text-[9px] font-black uppercase border border-rose-500/20 w-fit">
                                            <i data-lucide="drumstick" class="size-3"></i> Non-Veg
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-6 px-8">
                                @if($item->available)
                                    <div class="inline-flex items-center px-5 py-2 rounded-full text-[11px] font-black uppercase border border-emerald-500/30 bg-emerald-500/10 text-emerald-500 shadow-sm">
                                        <span class="size-2 rounded-full mr-2.5 bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                                        Available
                                    </div>
                                @else
                                    <div class="inline-flex items-center px-5 py-2 rounded-full text-[11px] font-black uppercase border border-rose-500/30 bg-rose-500/10 text-rose-500 shadow-sm">
                                        <span class="size-2 rounded-full mr-2.5 bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]"></span>
                                        Unavailable
                                    </div>
                                @endif
                            </td>
                            <td class="py-6 px-8 text-right">
                                <div class="flex items-center justify-end gap-2 px-2">
                                    <button 
                                        wire:click="confirmAIImageGeneration('{{ $item->id }}')" 
                                        wire:loading.attr="disabled"
                                        class="size-11 rounded-2xl flex items-center justify-center text-white bg-gradient-to-tr from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 transition-all shadow-lg shadow-purple-500/20 disabled:opacity-50 group/ai relative"
                                        title="Generate AI Image"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" wire:loading.remove wire:target="confirmAIImageGeneration('{{ $item->id }}'), generateAIImage('{{ $item->id }}')"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>
                                        <div wire:loading wire:target="confirmAIImageGeneration('{{ $item->id }}'), generateAIImage('{{ $item->id }}')">
                                            <i data-lucide="refresh-cw" class="size-5 animate-spin"></i>
                                        </div>
                                    </button>
                                    <button wire:click="openEdit('{{ $item->id }}')" class="size-11 rounded-2xl flex items-center justify-center text-muted-foreground border border-border hover:bg-primary/10 hover:text-primary hover:border-primary/20 transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </button>
                                    <button 
                                        wire:confirm="Are you sure you want to delete this item? This action cannot be undone."
                                        wire:click="delete('{{ $item->id }}')" 
                                        class="size-11 rounded-2xl flex items-center justify-center text-muted-foreground border border-border hover:bg-destructive/10 hover:text-destructive hover:border-destructive/20 transition-all shadow-sm"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-32 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="size-24 bg-muted rounded-full flex items-center justify-center mb-6 border border-border">
                                        <i data-lucide="search-x" class="size-12 text-muted-foreground/30"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-foreground mb-2">No menu items found</h3>
                                    <p class="text-muted-foreground font-medium max-w-sm mx-auto mb-10">
                                        {{ $search ? "We couldn't find any dishes matching your current search criteria." : "Your menu is looking a bit hungry. Start adding items!" }}
                                    </p>
                                    <div class="flex gap-4">
                                        @if($search || $filterStatus !== 'all' || $filterType !== 'all' || $selectedCategory !== 'All')
                                            <button 
                                                wire:click="clearFilters"
                                                class="h-14 px-8 rounded-2xl border-2 border-border text-muted-foreground font-black text-xs uppercase tracking-widest hover:bg-muted transition-all"
                                            >
                                                Clear Search
                                            </button>
                                        @endif
                                        <button 
                                            @click="$wire.openCreate()"
                                            class="h-14 px-8 bg-primary text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3"
                                        >
                                            <i data-lucide="plus-circle" class="size-5"></i>
                                            Add First Item
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-8 py-8 border-t border-border bg-muted/20">
            {{ $items->links() }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div 
        x-show="showAddDialog" 
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background/80"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            class="bg-card w-full max-w-2xl rounded-[3.5rem] shadow-2xl border border-border overflow-hidden"
            @click.away="showAddDialog = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <!-- Modal Header -->
            <div class="px-10 pt-10 pb-8 flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-foreground tracking-tight">{{ $editingItemId ? 'Update Dish' : 'New Dish Selection' }}</h2>
                    <p class="text-muted-foreground font-bold uppercase text-[10px] tracking-[0.2em] mt-2">Menu Inventory Registry</p>
                </div>
                <button @click="showAddDialog = false" class="size-14 flex items-center justify-center rounded-[1.5rem] bg-muted/40 hover:bg-muted/60 transition-colors border border-border">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="px-10 pb-10 max-h-[70vh] overflow-y-auto no-scrollbar space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-muted-foreground uppercase tracking-widest ml-1">Dish Identity</label>
                        <input type="text" wire:model="name" placeholder="e.g. Classic Margherita" class="h-16 w-full px-6 rounded-2xl bg-muted/30 border-2 border-border/50 focus:border-primary focus:bg-background focus:outline-none transition-all text-base font-bold text-foreground">
                        @error('name') <span class="text-[10px] text-rose-500 font-bold uppercase ml-1">{{ $message }}</span> @enderror
                    </div>

                        <div class="relative" x-data="{ open: false }">
                            <button 
                                type="button"
                                @click="open = !open"
                                class="h-16 w-full px-6 rounded-2xl bg-muted/30 border-2 border-border/50 flex items-center justify-between text-base font-bold text-foreground transition-all hover:bg-muted/40 outline-none focus:border-primary"
                            >
                                <span>{{ $categories->find($categoryId)?->name ?? 'Select Category' }}</span>
                                <i data-lucide="chevron-down" class="size-6 opacity-40 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div 
                                x-show="open" 
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                class="absolute top-full mt-2 left-0 w-full bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[110] backdrop-blur-xl"
                                x-cloak
                            >
                                @foreach($categories as $category)
                                    <button 
                                        type="button"
                                        @click="$wire.set('categoryId', '{{ $category->id }}'); open = false"
                                        class="w-full px-6 py-4 text-left text-sm font-bold hover:bg-primary/10 transition-colors {{ $categoryId === $category->id ? 'text-primary bg-primary/5' : 'text-foreground' }}"
                                    >
                                        {{ $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                </div>

                <div class="space-y-4">
                    <label class="text-[11px] font-black text-muted-foreground uppercase tracking-widest ml-1">Composition Description</label>
                    <textarea wire:model="description" rows="3" placeholder="Briefly describe the dish ingredients and preparation..." class="w-full p-6 rounded-[2rem] bg-muted/30 border-2 border-border/50 focus:border-primary focus:bg-background focus:outline-none transition-all text-base font-medium text-foreground/80 resize-none"></textarea>
                </div>

                <!-- Dish Image Section -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between ml-1">
                        <label class="text-[11px] font-black text-muted-foreground uppercase tracking-widest">Dish Image</label>
                        <div class="flex items-center gap-4">
                                <button 
                                    type="button"
                                    @if($editingItemId)
                                        wire:click="confirmAIImageGeneration('{{ $editingItemId }}')"
                                    @else
                                        wire:click="$set('generateOnSave', true); $wire.save()"
                                    @endif
                                    class="flex items-center gap-2 px-4 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-500 hover:bg-purple-500 hover:text-white transition-all text-[10px] font-black uppercase tracking-wider"
                                    wire:loading.attr="disabled"
                                >
                                    <i data-lucide="sparkles" class="size-3.5" wire:loading.remove wire:target="confirmAIImageGeneration, generateAIImage, save"></i>
                                    <div wire:loading wire:target="confirmAIImageGeneration, generateAIImage, save">
                                        <i data-lucide="refresh-cw" class="size-3.5 animate-spin"></i>
                                    </div>
                                    <span>Generate with AI</span>
                                </button>
                            <div wire:loading wire:target="image" class="flex items-center gap-2">
                                 <div class="size-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                 <span class="text-[9px] font-black text-primary uppercase tracking-[0.2em]">Uploading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div 
                        class="relative group"
                        x-data="{ isDragging: false }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false"
                    >
                        <!-- Upload Box / Preview -->
                        <div @class([
                            'relative min-h-[220px] rounded-[2.5rem] border-2 border-dashed transition-all overflow-hidden flex flex-col items-center justify-center p-8',
                            'bg-primary/5 border-primary shadow-[0_0_30px_rgba(255,107,0,0.1)]' => false, // We use Livewire state
                            'bg-muted/30 border-border/50 hover:border-primary/40 hover:bg-muted/40' => true
                        ])
                        :class="isDragging ? 'border-primary bg-primary/5 scale-[1.01]' : ''"
                        >
                            @if($image)
                                <!-- New Image Preview -->
                                <div class="absolute inset-0 group/preview">
                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/preview:opacity-100 transition-all flex items-center justify-center gap-4">
                                        <button type="button" @click="$refs.fileInput.click()" class="size-14 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-all hover:scale-110">
                                            <i data-lucide="refresh-cw" class="size-6"></i>
                                        </button>
                                        <button type="button" wire:click="$set('image', null)" class="size-14 rounded-2xl bg-rose-500/20 hover:bg-rose-500 border border-rose-500/30 flex items-center justify-center text-rose-500 hover:text-white transition-all hover:scale-110">
                                            <i data-lucide="trash-2" class="size-6"></i>
                                        </button>
                                    </div>
                                    <!-- Camera Overlay -->
                                    <div class="absolute top-6 right-6 size-10 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 flex items-center justify-center text-white/80 pointer-events-none">
                                        <i data-lucide="camera" class="size-5"></i>
                                    </div>
                                </div>
                            @elseif($currentImage)
                                <!-- Existing Image Preview -->
                                <div class="absolute inset-0 group/preview">
                                    <img src="{{ Storage::url($currentImage) . '?t=' . time() }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/preview:opacity-100 transition-all flex items-center justify-center gap-4">
                                        <button type="button" @click="$refs.fileInput.click()" class="size-14 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-all hover:scale-110">
                                            <i data-lucide="refresh-cw" class="size-6"></i>
                                        </button>
                                        <button type="button" wire:click="$set('currentImage', null)" class="size-14 rounded-2xl bg-rose-500/20 hover:bg-rose-500 border border-rose-500/30 flex items-center justify-center text-rose-500 hover:text-white transition-all hover:scale-110">
                                            <i data-lucide="trash-2" class="size-6"></i>
                                        </button>
                                    </div>
                                    <div class="absolute top-6 right-6 size-10 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 flex items-center justify-center text-white/80 pointer-events-none">
                                        <i data-lucide="image" class="size-5"></i>
                                    </div>
                                </div>
                            @else
                                <!-- Empty State -->
                                <div class="flex flex-col items-center text-center cursor-pointer" @click="$refs.fileInput.click()">
                                    <div class="size-20 rounded-[2rem] bg-muted flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all border border-border/50 shadow-inner">
                                        <i data-lucide="cloud-upload" class="size-10 text-muted-foreground/40 group-hover:text-primary transition-colors"></i>
                                    </div>
                                    <h4 class="text-base font-black text-foreground mb-1 italic">Drop your culinary masterpiece here</h4>
                                    <p class="text-xs text-muted-foreground font-bold uppercase tracking-widest opacity-60">or click to browse local files</p>
                                    <div class="mt-6 px-5 py-2 rounded-xl bg-background border border-border/50 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground group-hover:text-primary group-hover:border-primary/20 transition-all">Support: JPG, PNG, WEBP (Max 2MB)</div>
                                </div>
                            @endif

                            <input type="file" x-ref="fileInput" wire:model="image" class="hidden" accept="image/png, image/jpeg, image/jpg, image/webp">
                        </div>
                    </div>
                    @error('image') <span class="text-[10px] text-rose-500 font-bold uppercase ml-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-muted-foreground uppercase tracking-widest ml-1">Unit Pricing ({{ $settings['currency'] }})</label>
                        <div class="relative group">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 font-black text-muted-foreground/60 text-lg group-focus-within:text-primary transition-colors">{{ $settings['currency'] }}</span>
                            <input type="number" step="0.01" wire:model="price" class="h-16 w-full pl-12 pr-6 rounded-2xl bg-muted/30 border-2 border-border/50 focus:border-primary focus:bg-background focus:outline-none transition-all font-black text-foreground">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-muted-foreground uppercase tracking-widest ml-1">Prep Window (Minutes)</label>
                        <div class="relative group">
                            <i data-lucide="clock" class="absolute left-6 top-1/2 -translate-y-1/2 text-muted-foreground/60 size-6 group-focus-within:text-primary transition-colors"></i>
                            <input type="number" wire:model="preparation_time" class="h-16 w-full pl-14 pr-6 rounded-2xl bg-muted/30 border-2 border-border/50 focus:border-primary focus:bg-background focus:outline-none transition-all font-black text-foreground">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <button type="button" @click="is_veg = !is_veg" class="flex items-center justify-between p-6 bg-muted/30 rounded-[1.5rem] border-2 transition-all outline-none" :class="is_veg ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-border/50'">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-[1.25rem] flex items-center justify-center transition-all" :class="is_veg ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-muted text-muted-foreground/60'">
                                <i data-lucide="leaf" class="size-6"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black uppercase tracking-widest" :class="is_veg ? 'text-emerald-500' : 'text-muted-foreground'">Veg Only</span>
                                <span class="text-[10px] font-bold text-muted-foreground/40 uppercase tracking-tighter">Dietary Protocol</span>
                            </div>
                        </div>
                        <div class="relative h-7 w-12 rounded-full p-1 transition-colors" :class="is_veg ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'">
                            <div class="size-5 rounded-full bg-white shadow-md transition-transform duration-200" :class="is_veg ? 'translate-x-5' : 'translate-x-0'"></div>
                        </div>
                    </button>

                    <button type="button" @click="available = !available" class="flex items-center justify-between p-6 bg-muted/30 rounded-[1.5rem] border-2 transition-all outline-none" :class="available ? 'border-primary/20 bg-primary/5' : 'border-border/50'">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-[1.25rem] flex items-center justify-center transition-all" :class="available ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-muted text-muted-foreground/60'">
                                <i data-lucide="zap" class="size-6"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black uppercase tracking-widest" :class="available ? 'text-primary' : 'text-muted-foreground'">Active</span>
                                <span class="text-[10px] font-bold text-muted-foreground/40 uppercase tracking-tighter">Sales Status</span>
                            </div>
                        </div>
                        <div class="relative h-7 w-12 rounded-full p-1 transition-colors" :class="available ? 'bg-primary' : 'bg-zinc-300 dark:bg-zinc-700'">
                            <div class="size-5 rounded-full bg-white shadow-md transition-transform duration-200" :class="available ? 'translate-x-5' : 'translate-x-0'"></div>
                        </div>
                    </button>
                </div>

                <!-- Modifiers Section (Only visible when editing) -->
                @if($editingItemId)
                    <div class="pt-8 border-t-2 border-dashed border-border/50 space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-foreground tracking-tight">Add-on Modifiers</h3>
                                <p class="text-muted-foreground/60 text-[10px] font-bold uppercase tracking-widest mt-1">Enhancement Inventory</p>
                            </div>
                        </div>

                        <div class="flex gap-4 p-6 bg-muted/30 rounded-[2rem] border-2 border-border/50">
                            <div class="flex-1 space-y-2">
                                <label class="text-[9px] font-black text-muted-foreground uppercase tracking-widest ml-1">Modifier Label</label>
                                <input type="text" wire:model="newModifierName" placeholder="Extra Cheese" class="h-12 w-full px-5 rounded-xl bg-background border border-border focus:border-primary focus:outline-none transition-all text-sm font-bold text-foreground">
                            </div>
                            <div class="w-32 space-y-2">
                                <label class="text-[9px] font-black text-muted-foreground uppercase tracking-widest ml-1">Rate</label>
                                <input type="number" wire:model="newModifierPrice" class="h-12 w-full px-5 rounded-xl bg-background border border-border focus:border-primary focus:outline-none transition-all text-sm font-black text-foreground">
                            </div>
                            <div class="flex items-end">
                                <button wire:click="addModifier" class="h-12 w-12 rounded-xl bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-110 active:scale-95 transition-all">
                                    <i data-lucide="plus" class="size-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($items->find($editingItemId)?->modifiers ?? [] as $modifier)
                                <div class="flex items-center justify-between p-4 bg-card border border-border/50 rounded-2xl shadow-sm group hover:border-primary/30 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                                            <i data-lucide="plus" class="size-5"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-foreground">{{ $modifier->name }}</div>
                                            <div class="text-[10px] font-black text-primary">{{ $settings['currency'] }}{{ number_format($modifier->price, 2) }}</div>
                                        </div>
                                    </div>
                                    <button wire:click="removeModifier('{{ $modifier->id }}')" class="size-8 rounded-lg flex items-center justify-center text-muted-foreground/40 hover:text-destructive hover:bg-destructive/10 transition-all">
                                        <i data-lucide="x" class="size-4"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="p-10 bg-muted/40 border-t border-border flex gap-6">
                <button @click="showAddDialog = false" class="flex-1 h-16 rounded-[1.5rem] font-black text-[11px] uppercase tracking-[0.2em] text-muted-foreground/60 hover:text-foreground transition-colors">Discard Changes</button>
                <button wire:click="save" class="flex-1 h-16 bg-gradient-to-r from-primary to-primary/80 text-white font-black text-[11px] uppercase tracking-[0.2em] rounded-[1.5rem] shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    {{ $editingItemId ? 'Commit Updates' : 'System Registration' }}
                </button>
            </div>
        </div>
    </div>
    @script
    <script>
        $wire.on('notify', (data) => {
            const payload = data[0] || data;
            if (window.showToast) {
                window.showToast(payload.message, payload.type);
            }
        });

        $wire.on('confirm-ai-generation', (data) => {
            const payload = data[0] || data;
            if (confirm('Image already exists, do you want to generate another image??')) {
                $wire.generateAIImage(payload.id);
            }
        });
    </script>
    @endscript

    <!-- Persistent AI Generation Alert -->
    <div 
        x-data="{ 
            status: @entangle('generationStatus'),
            timer: null
        }"
        x-show="status !== ''"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-90"
        @generation-finished.window="
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => { status = ''; }, 5000);
        "
        class="fixed bottom-8 right-8 z-[200] pointer-events-auto bg-card border border-border/60 shadow-2xl rounded-2xl px-5 py-4 flex items-center gap-4 min-w-[320px] backdrop-blur-xl"
    >
        <div 
            :class="status === 'Image generated successfully!' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-blue-500/10 text-blue-500'" 
            class="p-2 rounded-xl transition-colors duration-300"
        >
            <i :data-lucide="status === 'Image generated successfully!' ? 'check-circle-2' : 'sparkles'" 
               :class="status === 'Image generated successfully!' ? '' : 'animate-pulse'"
               class="size-5 transition-all"></i>
        </div>
        
        <div class="flex-1">
            <p x-text="status" class="text-sm font-bold text-foreground leading-tight tracking-tight"></p>
        </div>

        <button @click="status = ''" class="text-muted-foreground hover:text-foreground transition-colors p-1">
            <i data-lucide="x" class="size-4"></i>
        </button>
    </div>
</div>
