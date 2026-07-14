@props(['label', 'value', 'trend' => null, 'icon' => 'trending-up', 'variant' => 'blue'])

@php
    $variants = [
        'blue' => 'bg-blue-500/10 text-blue-500',
        'orange' => 'bg-orange-500/10 text-orange-500',
        'indigo' => 'bg-indigo-500/10 text-indigo-500',
        'emerald' => 'bg-emerald-500/10 text-emerald-500',
    ];
    $variantClass = $variants[$variant] ?? $variants['blue'];
@endphp

<div class="bg-card rounded-2xl p-6 border border-border shadow-sm transition-all hover:shadow-md hover:border-primary/20 group">
    <div class="flex items-center justify-between mb-4">
        <div class="p-2.5 rounded-xl {{ $variantClass }} group-hover:scale-110 transition-transform">
            <i data-lucide="{{ $icon }}" class="size-6 font-bold"></i>
        </div>
        @if($trend)
            <div class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-[10px] font-extrabold uppercase tracking-wider">
                <i data-lucide="trending-up" class="size-3"></i>
                {{ $trend }}
            </div>
        @endif
    </div>
    <div>
        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-1">{{ $label }}</h3>
        <p class="text-[28px] font-extrabold text-foreground tracking-tight leading-none">{{ $value }}</p>
    </div>
</div>
