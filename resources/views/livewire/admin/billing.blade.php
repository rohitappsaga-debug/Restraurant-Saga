<div class="space-y-8" x-data="{ 
    showBillDialog: @entangle('showBillDialog'),
    printReceipt() {
        window.print();
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Billing Console</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">View and manage bills for all orders</p>
        </div>
        <div class="flex items-center gap-3 bg-card border border-border/50 p-2.5 rounded-2xl shadow-sm">
            <div class="size-10 flex items-center justify-center bg-primary/10 rounded-xl text-primary">
                <i data-lucide="receipt" class="size-5"></i>
            </div>
            <div class="pr-2">
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-1">Accounting Status</p>
                <p class="text-xs font-bold text-foreground">Terminal Active</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-col md:flex-row gap-4 px-2">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40"></i>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by order ID or table number..."
                class="pl-14 h-14 w-full bg-card border border-border/50 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-primary/10 transition-all outline-none"
            />
        </div>
        <div class="relative" x-data="{ open: false }">
            <button 
                @click="open = !open"
                class="h-14 px-6 rounded-2xl bg-card border border-border/50 text-sm font-bold flex items-center justify-between min-w-[200px] hover:border-primary/30 transition-all outline-none focus:ring-4 focus:ring-primary/10"
            >
                <span class="uppercase tracking-widest text-[10px]">
                    {{ [
                        'all' => 'Historical Ledger',
                        'today' => 'Today Only',
                        '7days' => 'Last 7 Days',
                        '30days' => 'Last 30 Days'
                    ][$dateFilter] ?? 'Filter Date' }}
                </span>
                <i data-lucide="chevron-down" class="size-4 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="absolute top-full mt-2 right-0 w-full min-w-[200px] bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[50] backdrop-blur-xl"
                x-cloak
            >
                @foreach(['all' => 'Historical Ledger', 'today' => 'Today Only', '7days' => 'Last 7 Days', '30days' => 'Last 30 Days'] as $val => $label)
                    <button 
                        @click="$wire.set('dateFilter', '{{ $val }}'); open = false"
                        class="w-full px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $dateFilter === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 px-2">
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm group hover:border-primary/20 transition-all text-center">
            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-4">Total Bills</p>
            <div class="text-4xl font-black text-foreground">{{ $stats['total_bills'] }}</div>
        </div>

        <div class="relative p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm group hover:border-amber-500/20 transition-all text-center">
            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-4">Pending</p>
            <div class="text-4xl font-black text-amber-500">{{ $stats['pending_count'] }}</div>
            
            <button 
                wire:click="clearAllPending" 
                wire:confirm="This will mark all orphaned unpaid bills as paid. Proceed?"
                class="absolute -bottom-3 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all px-4 py-1.5 bg-amber-500 text-white text-[9px] font-black uppercase tracking-widest rounded-full shadow-lg shadow-amber-500/20"
            >
                Clear All
            </button>
        </div>

        <div class="p-8 bg-primary rounded-[2rem] shadow-lg shadow-primary/20 transition-all text-center">
            <p class="text-[10px] font-black text-primary-foreground/60 uppercase tracking-widest mb-4">Paid Today</p>
            <div class="text-4xl font-black text-primary-foreground">{{ $settings['currency'] }}{{ number_format($stats['paid_today'], 2) }}</div>
        </div>
    </div>

    <!-- Pending Payments -->
    @if($pendingOrders->isNotEmpty())
        <div class="space-y-4 px-2">
            <div class="flex items-center gap-3">
                <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                <h2 class="text-xs font-black text-foreground uppercase tracking-widest">Pending Payments</h2>
            </div>
            <div class="grid grid-cols-1 gap-4">
                @foreach($pendingOrders as $order)
                    @php $details = $this->calculateBillDetails($order); @endphp
                    <div wire:key="pending-{{ $order->id }}" class="p-5 bg-card border border-border/50 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 hover:shadow-md transition-shadow">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="space-y-1">
                                <span class="text-sm font-bold text-foreground">Order #ORD-{{ substr(strtoupper($order->id), -6) }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-muted text-muted-foreground text-[10px] font-bold uppercase tracking-widest">Table {{ $order->table_number }}</span>
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-600 text-[10px] font-extrabold uppercase tracking-widest border border-amber-500/20">Pending</span>
                                </div>
                            </div>
                            <div class="h-8 w-px bg-border hidden md:block"></div>
                            <div class="text-[11px] font-medium text-muted-foreground">
                                <span class="font-bold text-foreground">{{ $order->orderItems->sum('quantity') }} items</span>
                                <span class="mx-1.5">•</span>
                                <span class="font-bold text-foreground">{{ $settings['currency'] }}{{ number_format($details['total'], 2) }}</span>
                                <span class="mx-1.5">•</span>
                                <span>{{ $order->created_at->format('h:i:s A') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="viewBill('{{ $order->id }}')"
                                class="h-11 px-4 rounded-xl bg-muted/50 hover:bg-muted text-foreground text-xs font-bold transition-all flex items-center gap-2"
                            >
                                <i data-lucide="eye" class="size-4 opacity-60"></i>
                                View
                            </button>
                            <button 
                                wire:click="markAsPaid('{{ $order->id }}')"
                                class="h-11 px-6 bg-emerald-500 text-white rounded-xl shadow-lg shadow-emerald-500/20 font-bold text-xs hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
                            >
                                <i data-lucide="check-circle" class="size-4"></i>
                                Mark Paid
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Transaction Ledger -->
    <div class="space-y-4 px-2 pb-12">
        <h2 class="text-xs font-black text-foreground uppercase tracking-widest">Paid Bills</h2>
        <div class="bg-card border border-border/50 rounded-3xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-muted/30 border-b border-border/50">
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Order ID</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Table</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Items</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Amount</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Payment</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Time</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Date</th>
                            <th class="py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/30">
                        @foreach($orders->where('is_paid', true) as $order)
                            <tr class="group hover:bg-muted/10 transition-colors">
                                <td class="py-5 px-8">
                                    <span class="text-xs font-bold text-foreground">#ORD-{{ substr(strtoupper($order->id), -6) }}</span>
                                </td>
                                <td class="py-5 px-8">
                                    <span class="px-2 py-1 rounded-lg bg-muted/50 text-foreground text-[10px] font-bold uppercase tracking-widest border border-border">Table {{ $order->table_number }}</span>
                                </td>
                                <td class="py-5 px-8 text-xs font-medium text-muted-foreground">
                                    {{ $order->orderItems?->sum('quantity') ?? 0 }}
                                </td>
                                <td class="py-5 px-8">
                                    <span class="text-sm font-bold text-foreground">{{ $settings['currency'] }}{{ number_format($order->total, 2) }}</span>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="flex items-center gap-2">
                                        <div class="size-1.5 rounded-full bg-emerald-500"></div>
                                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">{{ $order->payment_method ?? 'CASH' }}</span>
                                    </div>
                                </td>
                                <td class="py-5 px-8 text-[10px] font-bold text-muted-foreground uppercase">
                                    {{ $order->created_at->format('h:i A') }}
                                </td>
                                <td class="py-5 px-8 text-xs font-medium text-muted-foreground">
                                    {{ $order->created_at->format('d M, Y') }}
                                </td>
                                <td class="py-5 px-8">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button wire:click="viewBill('{{ $order->id }}')" class="size-9 rounded-lg bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-primary/10 hover:text-primary transition-all">
                                            <i data-lucide="eye" class="size-4"></i>
                                        </button>
                                        <button class="size-9 rounded-lg bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-muted transition-all">
                                            <i data-lucide="printer" class="size-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->where('is_paid', true)->isEmpty())
                <div class="py-24 text-center">
                    <i data-lucide="clipboard-list" class="size-12 text-muted-foreground/20 mx-auto mb-4"></i>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">No paid transactions found</p>
                </div>
            @endif

            <div class="p-6 border-t border-border/30 bg-muted/5">
                {{ $orders->links() }}
            </div>
        </div>
    </div>

    <!-- Bill Detail Modal -->
    <div 
        x-show="showBillDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/60 backdrop-blur-xl"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-cloak
    >
        <div 
            class="bg-card w-full max-w-[500px] max-h-[90vh] rounded-[2rem] shadow-2xl overflow-hidden border border-border relative text-foreground flex flex-col"
            @click.away="showBillDialog = false"
            id="printable-bill"
        >
            @if($selectedOrder)
                <!-- Receipt Header -->
                <div class="p-8 text-center border-b border-dashed border-border/50 relative">
                    <button @click="showBillDialog = false" class="absolute right-6 top-6 size-8 flex items-center justify-center rounded-lg bg-muted hover:bg-muted/80 transition-all group print:hidden">
                        <i data-lucide="x" class="size-4 text-muted-foreground"></i>
                    </button>
                    
                    <div class="text-2xl font-black tracking-tighter uppercase mb-1">RESTAURANT</div>
                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-4">Bill Summary & {{ $settings['taxEnabled'] ? 'Tax Invoice' : 'Invoice' }}</p>
                    <div class="flex items-center justify-center gap-3 mt-4 text-[10px] font-black uppercase text-muted-foreground opacity-60">
                        <span>{{ $selectedOrder->created_at->format('d M Y') }}</span>
                        <span class="size-1 rounded-full bg-border"></span>
                        <span>#ORD-{{ substr(strtoupper($selectedOrder->id), -6) }}</span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto no-scrollbar">
                    <!-- Order Items -->
                    <div class="px-8 py-6 flex items-center justify-between border-b border-border/50 bg-muted/20">
                        <div>
                            <h2 class="text-xl font-black text-foreground tracking-tight">Order Items</h2>
                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1">Full Transaction Breakdown</p>
                        </div>
                        <div class="px-4 py-1.5 bg-primary/10 border border-primary/20 rounded-full">
                            <span class="text-xs font-black text-primary uppercase">{{ $selectedOrder->orderItems->count() }} Items</span>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        @foreach($selectedOrder->orderItems as $item)
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-foreground">{{ $item->menuItem->name }}</h4>
                                        <span class="font-black text-foreground tracking-tight">{{ $settings['currency'] }}{{ number_format($item->price * $item->quantity, 2) }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="text-xs font-black text-muted-foreground uppercase tracking-tighter">{{ $item->quantity }} &times; {{ $settings['currency'] }}{{ number_format($item->price, 2) }}</span>
                                        @if($item->menuItem->is_veg)
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                            <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">VEG</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-8 py-8 space-y-6 border-t border-border/50">
                        @php $bill = $this->calculateBillDetails($selectedOrder); @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-muted-foreground uppercase tracking-widest">Gross Subtotal</span>
                            <span class="text-lg font-black text-foreground tracking-tight">{{ $settings['currency'] }}{{ number_format($bill['subtotal'], 2) }}</span>
                        </div>

                        @if($settings['taxEnabled'])
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span class="text-xs font-bold uppercase tracking-widest">GST ({{ (int)$bill['taxRate'] }}%)</span>
                                <span class="text-sm font-black tracking-tight">+ {{ $settings['currency'] }}{{ number_format($bill['tax'], 2) }}</span>
                            </div>
                        @endif

                        <div class="space-y-4 pt-4 border-t border-dashed border-border/50">
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <span class="text-sm font-black text-foreground uppercase tracking-widest">Apply Discount</span>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-tighter">Percentage reduction</p>
                                </div>
                                <div class="w-24 relative">
                                    <input 
                                        type="number" 
                                        wire:model.live.debounce.500ms="discount" 
                                        class="w-full h-11 pl-4 pr-10 rounded-xl bg-card border border-border focus:border-primary focus:outline-none text-sm font-bold text-foreground text-center"
                                        placeholder="0"
                                    >
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-muted-foreground/60">%</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 pt-2">
                                @foreach($discountPresets as $preset)
                                    <button 
                                        wire:click="$set('discount', {{ $preset }})"
                                        class="px-3 py-1.5 rounded-lg border border-border text-[10px] font-black uppercase transition-all {{ (float)$discount === (float)$preset ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}"
                                    >
                                        {{ $preset }}%
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-8 mt-2 border-t border-dashed border-border flex items-center justify-between">
                            <div>
                                <span class="text-sm font-black text-foreground uppercase tracking-widest">Final Total</span>
                                <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-tighter mt-1">{{ $taxEnabled ? 'Incl. all applicable levies' : 'Taxes excluded' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-black text-primary tracking-tighter">{{ $settings['currency'] }}{{ number_format($bill['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if(!$selectedOrder->is_paid)
                        <div class="p-8 bg-muted/40 border-t border-border mt-auto">
                            <h3 class="text-sm font-black text-foreground uppercase tracking-widest mb-6">Settlement Channel</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                @foreach($paymentMethods as $key => $method)
                                    <button 
                                        wire:click="$set('paymentMethod', '{{ $key }}')"
                                        class="group flex flex-col items-center gap-4 p-5 rounded-[2rem] border-2 transition-all duration-300 {{ $paymentMethod === $key ? 'bg-primary border-primary shadow-2xl shadow-primary/20 scale-[1.02]' : 'bg-card border-border/50 grayscale opacity-40 hover:opacity-100 hover:grayscale-0 hover:border-primary/30' }}"
                                    >
                                        <div class="size-16 rounded-2xl flex items-center justify-center transition-all duration-300 {{ $paymentMethod === $key ? 'bg-white/20 text-white rotate-6' : 'bg-background text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary' }}">
                                            <i data-lucide="{{ $method['icon'] }}" class="size-8"></i>
                                        </div>
                                        <span class="text-[11px] font-black uppercase tracking-widest {{ $paymentMethod === $key ? 'text-white' : 'text-foreground' }}">{{ $method['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <button 
                                wire:click="processPayment"
                                wire:loading.attr="disabled"
                                class="w-full h-18 mt-8 bg-gradient-to-r from-primary to-primary/80 text-white font-black text-xs uppercase tracking-[0.25em] rounded-[2rem] shadow-2xl shadow-primary/20 hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed group relative overflow-hidden"
                            >
                                <span wire:loading.remove>Settle & Close Transaction</span>
                                <span wire:loading>Processing...</span>
                            </button>
                        </div>
                    @else
                        <div class="p-8 bg-muted/40 border-t border-border text-center">
                            <button @click="printReceipt()" class="w-full h-14 bg-card border border-border rounded-xl flex items-center justify-center gap-2 hover:bg-muted transition-all font-black text-xs uppercase tracking-widest text-foreground">
                                <i data-lucide="printer" class="size-4"></i>
                                Print Final Receipt
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            body * {
                visibility: hidden!important;
            }
            #printable-bill, #printable-bill * {
                visibility: visible!important;
                color: #000 !important;
                background: #fff !important;
            }
            #printable-bill {
                position: fixed!important;
                left: 0!important;
                top: 0!important;
                width: 100%!important;
                border: none !important;
                box-shadow: none !important;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>

    @script
    <script>
        $wire.on('notify', (data) => {
            const payload = data[0] || data;
            if (window.showToast) {
                window.showToast(payload.message, payload.type);
            }
        });
    </script>
    @endscript
</div>
