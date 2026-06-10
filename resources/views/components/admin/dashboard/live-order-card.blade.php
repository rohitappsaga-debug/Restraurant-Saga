@props(['order'])

<div class="bg-card rounded-2xl p-5 border border-border shadow-sm flex items-center gap-5 transition-all hover:bg-muted/30 group border-l-4 border-l-primary">
    <div class="size-14 rounded-xl bg-primary/10 flex flex-col items-center justify-center border border-primary/20 shrink-0">
        <span class="text-[9px] font-black text-primary/60 uppercase tracking-tighter leading-none">TBL</span>
        <span class="text-lg font-black text-primary leading-tight">{{ $order->table_number }}</span>
    </div>
    
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-1">
            <h4 class="font-extrabold text-foreground truncate text-[14px]">Order #{{ substr($order->id, -4) }}</h4>
            <span class="px-2 py-0.5 rounded-md bg-warning/10 text-warning text-[9px] font-black uppercase tracking-widest border border-warning/20">
                {{ $order->status->value }}
            </span>
        </div>
        <p class="text-[13px] text-muted-foreground font-medium truncate opacity-80">
            {{ $order->orderItems->map(fn($item) => $item->quantity . 'x ' . ($item->menuItem?->name ?? 'Item'))->join(', ') }}
        </p>
        <div class="flex items-center gap-2 mt-2">
            <i data-lucide="clock" class="size-3.5 text-muted-foreground/60"></i>
            <span class="text-[10px] font-bold text-muted-foreground tracking-wide">{{ $order->created_at->diffForHumans() }}</span>
        </div>
    </div>

    <button class="p-2 rounded-xl text-muted-foreground group-hover:text-primary group-hover:bg-primary/10 transition-all active:scale-90 shrink-0">
        <i data-lucide="chevron-right" class="size-6"></i>
    </button>
</div>
