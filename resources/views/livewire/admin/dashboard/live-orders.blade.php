<div>
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-lg font-extrabold text-foreground tracking-tight">Live Orders <span class="text-muted-foreground font-normal ml-1">({{ $liveOrders->count() }})</span></h2>
        <a href="{{ route('admin.orders') }}" wire:navigate.hover class="text-[11px] font-black text-primary hover:bg-primary hover:text-white flex items-center gap-1.5 transition-all group uppercase tracking-[0.2em] bg-primary/10 px-4 py-2 min-h-[44px] rounded-xl">
            View All
            <i data-lucide="chevron-right" class="size-4 group-hover:translate-x-1 transition-transform"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($liveOrders as $order)
            <x-admin.dashboard.live-order-card :order="$order" />
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center bg-card rounded-[2rem] border-2 border-dashed border-border">
            <div class="size-16 rounded-full bg-muted flex items-center justify-center mb-4">
                <i data-lucide="inbox" class="size-8 text-muted-foreground"></i>
            </div>
                <p class="text-xs font-bold text-muted-foreground uppercase tracking-[0.2em]">No active orders right now</p>
                <p class="text-[10px] text-muted-foreground mt-2">New orders will appear here automatically</p>
            </div>
        @endforelse
    </div>
</div>
