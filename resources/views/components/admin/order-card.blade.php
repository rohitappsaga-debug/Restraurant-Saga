@props(['order'])

@php
    $status = $order->status->value;
    $statusConfig = match($status) {
        'pending' => ['bg' => 'bg-indigo-500/10', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500/20', 'icon' => 'clock'],
        'preparing' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-600', 'border' => 'border-orange-500/20', 'icon' => 'flame'],
        'ready' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-600', 'border' => 'border-emerald-500/20', 'icon' => 'check-circle-2'],
        'served', 'delivered' => ['bg' => 'bg-muted', 'text' => 'text-muted-foreground', 'border' => 'border-border', 'icon' => 'user-check'],
        'cancelled' => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-600', 'border' => 'border-rose-500/20', 'icon' => 'x-circle'],
        default => ['bg' => 'bg-muted', 'text' => 'text-muted-foreground', 'border' => 'border-border', 'icon' => 'help-circle'],
    };

    $minutes = (int) $order->created_at->diffInMinutes(now());
    $timeDisplay = $minutes < 60 ? $minutes . 'm elapsed' : $order->created_at->diffForHumans(['short' => true]);
@endphp

<div 
    class="group relative flex items-center gap-6 p-5 bg-card/60 backdrop-blur-md border border-border/50 rounded-[2rem] hover:shadow-2xl hover:shadow-primary/5 transition-all duration-500 hover:-translate-y-1"
>
    <!-- Table ID Block -->
    <div class="flex-shrink-0 size-20 rounded-[1.5rem] bg-muted/50 border border-border/50 flex flex-col items-center justify-center shadow-inner group-hover:border-primary/30 transition-colors">
        <span class="text-[10px] font-black text-muted-foreground/40 uppercase tracking-widest leading-none mb-1">TBL</span>
        <span class="text-3xl font-black text-foreground">{{ $order->table_number ?? '?' }}</span>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0 pr-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Order #{{ substr($order->id, 0, 4) }}</span>
                <button 
                    wire:click="viewOrderDetails('{{ $order->id }}')"
                    class="size-6 rounded-lg bg-muted text-muted-foreground hover:bg-primary/10 hover:text-primary transition-all flex items-center justify-center p-0 border-0"
                >
                    <i data-lucide="eye" class="size-3"></i>
                </button>
            </div>
            <div class="px-2.5 py-1 rounded-lg {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} {{ $statusConfig['border'] }} border text-[9px] font-black uppercase tracking-widest flex items-center gap-1.5 backdrop-blur-sm">
                <i data-lucide="{{ $statusConfig['icon'] }}" class="size-3"></i>
                {{ $status }}
            </div>
        </div>

        <h3 class="font-bold text-foreground text-sm line-clamp-1 mb-1">
            @foreach($order->orderItems as $idx => $item)
                {{ $item->quantity }}x {{ $item->menuItem->name }}{{ !$loop->last ? ',' : '' }}
            @endforeach
        </h3>

        <div class="flex items-center gap-2 text-xs text-muted-foreground mt-2">
            <i data-lucide="clock" class="size-3.5"></i>
            <span class="font-medium">{{ $timeDisplay }}</span>
        </div>
    </div>

    <!-- Quick Actions Hover Overlay -->
    <div class="absolute inset-x-5 bottom-4 translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 flex items-center gap-2 pointer-events-none group-hover:pointer-events-auto">
        @if($status === 'pending')
            <button 
                wire:click="updateOrderStatus('{{ $order->id }}', 'preparing')"
                class="flex-1 h-10 bg-orange-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-orange-500/20 hover:scale-[1.02] active:scale-95 transition-all"
            >
                Start Cooking
            </button>
        @elseif($status === 'preparing')
            <button 
                wire:click="updateOrderStatus('{{ $order->id }}', 'ready')"
                class="flex-1 h-10 bg-emerald-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:scale-[1.02] active:scale-95 transition-all"
            >
                Mark Ready
            </button>
        @elseif($status === 'ready')
            <button 
                wire:click="updateOrderStatus('{{ $order->id }}', 'served')"
                class="flex-1 h-10 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all"
            >
                Serve Order
            </button>
        @endif
    </div>
</div>
