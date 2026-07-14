<div class="space-y-8" x-data="{ 
    activeTab: @entangle('activeTab'),
    showDetailModal: @entangle('showDetailModal')
}">
    <!-- Header & Search -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Orders</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Monitor and manage live restaurant traffic</p>
        </div>

        <div class="flex items-center gap-4 w-full lg:w-max">
            <div class="relative flex-1 lg:w-80 group">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground/60 group-focus-within:text-primary transition-colors"></i>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by ID or Table..."
                    class="w-full h-14 pl-12 pr-4 rounded-2xl bg-card border border-border/50 focus:bg-background focus:ring-4 focus:ring-primary/10 transition-all outline-none font-medium shadow-sm"
                >
            </div>
            <button aria-label="Filter orders" class="size-14 rounded-2xl bg-card border border-border/50 flex items-center justify-center text-muted-foreground hover:text-primary transition-all shadow-sm">
                <i data-lucide="filter" class="size-5"></i>
            </button>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide px-2">
        <button 
            wire:click="setTab('all')"
            class="h-11 px-6 rounded-full font-bold text-sm whitespace-nowrap transition-all flex items-center gap-2
            {{ $activeTab === 'all' ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-card border border-border/50 text-muted-foreground hover:bg-muted' }}"
        >
            All
        </button>

        @foreach(\App\Enums\OrderStatus::cases() as $status)
            <button 
                wire:click="setTab('{{ $status->value }}')"
                class="h-11 px-6 rounded-full font-bold text-sm whitespace-nowrap transition-all flex items-center gap-2
                {{ $activeTab === $status->value ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-card border border-border/50 text-muted-foreground hover:bg-muted' }}"
            >
                {{ ucfirst($status->value) }}
                @if(isset($statusCounts[$status->value]) && $statusCounts[$status->value] > 0)
                    <span class="size-5 rounded-full bg-foreground/10 flex items-center justify-center text-[10px]">{{ $statusCounts[$status->value] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Orders Feed -->
    <div class="space-y-10 px-2">
        @if($activeTab === 'all')
            <!-- Active Orders Section -->
            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.2em]">Active Orders ({{ $activeOrders->count() }})</h2>
                </div>

                @if($activeOrders->isEmpty())
                    <div class="py-20 text-center bg-card/40 border border-dashed border-border/50 rounded-[3rem]">
                        <div class="size-20 bg-muted/30 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="chef-hat" class="size-10 text-muted-foreground/40"></i>
                        </div>
                        <h3 class="text-xl font-bold text-foreground">Kitchen is quiet</h3>
                        <p class="text-muted-foreground mt-2 font-medium">No active orders currently processing</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                        @foreach($activeOrders as $order)
                            <x-admin.order-card :order="$order" />
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Past Orders Section -->
            <section class="space-y-6 pt-10 border-t border-border/40">
                <div class="flex items-center gap-3">
                    <i data-lucide="history" class="size-4 text-muted-foreground"></i>
                    <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.2em]">Recently Served</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 hover:opacity-100 transition-all duration-500">
                    @foreach($pastOrders as $order)
                        <x-admin.order-card :order="$order" />
                    @endforeach
                </div>
                
                <div class="pt-4">
                    {{ $pastOrders->links() }}
                </div>
            </section>
        @else
            <!-- Filtered Orders (Single View) -->
            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.2em]">{{ strtoupper($activeTab) }} Orders ({{ $filteredOrders->total() }})</h2>
                </div>

                @if($filteredOrders->isEmpty())
                    <div class="py-20 text-center bg-card/40 border border-dashed border-border/50 rounded-[3rem]">
                        <div class="size-20 bg-muted/30 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="inbox" class="size-10 text-muted-foreground/40"></i>
                        </div>
                        <h3 class="text-xl font-bold text-foreground">Nothing here</h3>
                        <p class="text-muted-foreground mt-2 font-medium">No orders match the current status</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                        @foreach($filteredOrders as $order)
                            <x-admin.order-card :order="$order" />
                        @endforeach
                    </div>
                    <div class="pt-8">
                        {{ $filteredOrders->links() }}
                    </div>
                @endif
            </section>
        @endif
    </div>

    @script
    <script>
        $wire.on('notify', (data) => {
            lucide.createIcons();
            const payload = data[0] || data;
            if (window.showToast) {
                window.showToast(payload.message, payload.type);
            }
        });

        lucide.createIcons();
        
        Livewire.hook('morph.updated', (el, component) => {
            lucide.createIcons();
        });
    </script>
    @endscript

    <!-- Detail Modal -->
    <div 
        x-show="showDetailModal" 
        x-cloak
        class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-background/80 backdrop-blur-xl"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            class="bg-card w-full max-w-2xl rounded-[3rem] shadow-2xl border border-border mt-10 overflow-hidden"
            @click.away="showDetailModal = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            @if($this->selectedOrder)
                <div class="p-8 space-y-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="size-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center border border-primary/20">
                                <i data-lucide="receipt" class="size-7"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-foreground tracking-tight">Order #{{ substr($this->selectedOrder->id, 0, 8) }}</h3>
                                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-1">
                                    Table {{ $this->selectedOrder->table_number }} &bull; {{ $this->selectedOrder->created_at->format('h:i A') }}
                                </p>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="size-12 rounded-xl bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors">
                            <i data-lucide="x" class="size-5"></i>
                        </button>
                    </div>

                    <!-- Items List -->
                    <div class="bg-muted/30 rounded-[2rem] border border-border/50 divide-y divide-border/50 overflow-hidden">
                        @foreach($this->selectedOrder->orderItems as $item)
                            <div class="p-6 flex items-start justify-between gap-4">
                                <div class="flex gap-4">
                                    <div class="size-10 rounded-xl bg-background border border-border/50 flex items-center justify-center font-black text-xs text-muted-foreground">
                                        {{ $item->quantity }}x
                                    </div>
                                    <div>
                                        <div class="font-bold text-foreground">{{ $item->menuItem->name }}</div>
                                        @if($item->modifiers->isNotEmpty())
                                            <div class="text-[10px] font-medium text-muted-foreground mt-1 flex flex-wrap gap-1">
                                                @foreach($item->modifiers as $mod)
                                                    <span class="bg-primary/5 text-primary px-1.5 py-0.5 rounded-md border border-primary/10">+ {{ $mod->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($item->notes)
                                            <p class="text-[10px] text-orange-500 font-bold mt-1 uppercase tracking-tight italic">"{{ $item->notes }}"</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm font-black text-foreground">
                                    {{ Setting::first()->currency }}{{ number_format($item->subtotal, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Totals -->
                    <div class="space-y-3 px-2">
                        <div class="flex justify-between text-xs font-bold text-muted-foreground">
                            <span>Subtotal</span>
                            <span>{{ Setting::first()->currency }}{{ number_format($this->selectedOrder->total - $this->selectedOrder->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-muted-foreground">
                            <span>Tax ({{ Setting::first()->tax_rate }}%)</span>
                            <span>{{ Setting::first()->currency }}{{ number_format($this->selectedOrder->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xl font-black text-foreground pt-3 border-t border-border/50">
                            <span>Total Amount</span>
                            <span class="text-primary">{{ Setting::first()->currency }}{{ number_format($this->selectedOrder->total, 2) }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-4">
                        @if($this->selectedOrder->status->value === 'pending')
                            <button wire:click="updateOrderStatus('{{ $this->selectedOrder->id }}', 'preparing')" class="flex-1 h-14 bg-orange-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-orange-500/20">Mark Preparing</button>
                        @elseif($this->selectedOrder->status->value === 'preparing')
                            <button wire:click="updateOrderStatus('{{ $this->selectedOrder->id }}', 'ready')" class="flex-1 h-14 bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-emerald-500/20">Mark Ready</button>
                        @elseif($this->selectedOrder->status->value === 'ready')
                            <button wire:click="updateOrderStatus('{{ $this->selectedOrder->id }}', 'served')" class="flex-1 h-14 bg-primary text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-primary/20">Served</button>
                        @endif
                        <button class="flex-1 h-14 bg-muted text-foreground rounded-2xl text-xs font-black uppercase tracking-widest border border-border/50 flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="size-4"></i>
                            Print Receipt
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
