<div class="space-y-8 px-2">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tight uppercase tracking-tighter">Centralized Integrity Log</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Immutable system audit trail and administrative forensics</p>
        </div>
        <div class="relative w-full md:w-[450px] group">
            <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40 group-focus-within:text-amber-500 transition-colors"></i>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Identify event by user ID, action vector, or metadata..."
                class="pl-14 h-14 w-full bg-card border border-border/60 rounded-[1.75rem] text-[10px] font-black uppercase tracking-widest focus:ring-4 focus:ring-amber-500/10 transition-all outline-none placeholder:opacity-30"
            />
        </div>
    </div>

    <!-- Analytics Matrix -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="history" class="size-32"></i>
            </div>
            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em] mb-3 relative z-10">Event Count</p>
            <div class="text-foreground font-black text-3xl tracking-tighter relative z-10 uppercase">{{ $logs->total() }} <span class="text-xs text-muted-foreground uppercase tracking-widest ml-1 opacity-40">Records</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="shield-alert" class="size-32 text-rose-500"></i>
            </div>
            <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.4em] mb-3 relative z-10">Security Events</p>
            <div class="text-rose-500 font-black text-3xl tracking-tighter relative z-10">3 <span class="text-xs opacity-40 uppercase tracking-widest ml-1">Critical</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="eye" class="size-32 text-blue-500"></i>
            </div>
            <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.4em] mb-3 relative z-10">Observation Index</p>
            <div class="text-blue-500 font-black text-3xl tracking-tighter relative z-10">99.9% <span class="text-xs opacity-40 uppercase tracking-widest ml-1">UP</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="bar-chart-3" class="size-32 text-emerald-500"></i>
            </div>
            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.4em] mb-3 relative z-10">Processing Latency</p>
            <div class="text-emerald-500 font-black text-3xl tracking-tighter relative z-10">12ms <span class="text-xs opacity-40 uppercase tracking-widest ml-1">AVG</span></div>
        </div>
    </div>

    <!-- Logs Repository -->
    <div class="bg-card border border-border rounded-[3.5rem] overflow-hidden shadow-glow-sm p-1">
        <div class="p-10 border-b border-border bg-muted/[0.15] flex items-center justify-between rounded-t-[3.25rem]">
            <div>
                <h3 class="text-xl font-black text-foreground uppercase tracking-tight">Persistence Stream</h3>
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-1">Sequential Integrity Record State</p>
            </div>
            <div class="px-4 py-2 bg-background/50 border border-border/50 rounded-full text-[9px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                <span class="size-2 bg-emerald-500 rounded-full animate-pulse"></span>
                Log Engine Active
            </div>
        </div>

        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="bg-muted/10">
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em]">Chronos Timestamp</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em]">Modification Vector</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em]">Operation Identity</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em]">Manifest Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($logs as $log)
                        <tr wire:key="log-{{ $log->id }}" class="group hover:bg-muted/20 transition-all">
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="size-2 bg-indigo-500/50 rounded-full group-hover:scale-150 transition-transform"></div>
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-black text-foreground font-mono tracking-tighter uppercase">{{ $log->created_at->format('M d • H:i:s') }}</p>
                                        <p class="text-[8px] font-black text-muted-foreground uppercase tracking-widest opacity-40">{{ $log->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="size-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white font-black text-xs shadow-glow shadow-indigo-500/10 group-hover:scale-110 transition-transform">
                                        {{ substr($log->user?->name ?? 'SYS', 0, 1) }}
                                    </div>
                                    <div class="space-y-1 min-w-0">
                                        <p class="text-xs font-black text-foreground uppercase tracking-tight truncate">{{ $log->user?->name ?? 'SYSTEM_KERNEL' }}</p>
                                        <p class="text-[8px] font-black px-2 py-0.5 rounded bg-muted/60 text-muted-foreground uppercase tracking-widest inline-block border border-border/50">
                                            {{ $log->user?->role->value ?? 'KERNEL' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-10">
                                @php
                                    $actionColor = match(true) {
                                        str_contains(strtolower($log->action), 'create') => 'emerald',
                                        str_contains(strtolower($log->action), 'delete') => 'rose',
                                        str_contains(strtolower($log->action), 'update') => 'blue',
                                        default => 'indigo'
                                    };
                                @endphp
                                <span class="px-4 py-2 rounded-xl bg-{{ $actionColor }}-500/10 text-{{ $actionColor }}-500 text-[9px] font-black uppercase tracking-[0.2em] border border-{{ $actionColor }}-500/20 shadow-sm relative overflow-hidden group/badge">
                                    <span class="relative z-10">{{ $log->action }}</span>
                                    <span class="absolute inset-0 bg-{{ $actionColor }}-500/5 translate-x-[-100%] group-hover/badge:translate-x-0 transition-transform duration-500"></span>
                                </span>
                            </td>
                            <td class="py-6 px-10">
                                <div class="p-4 bg-muted/30 border border-border/50 rounded-2xl max-w-lg overflow-hidden relative">
                                    <p class="text-[10px] font-medium text-muted-foreground leading-relaxed font-mono tracking-tighter group-hover:text-foreground transition-colors overflow-x-auto no-scrollbar" title="{{ $log->details }}">
                                        {{ $log->details ?: 'NULL_METADATA_MANIFEST' }}
                                    </p>
                                    <div class="absolute right-4 top-4 opacity-0 group-hover:opacity-30 transition-opacity">
                                        <i data-lucide="info" class="size-3.5"></i>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-32 text-center relative overflow-hidden">
                                <i data-lucide="calendar-off" class="size-40 text-muted-foreground opacity-[0.03] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></i>
                                <div class="relative z-10 space-y-4">
                                    <div class="size-20 bg-muted/50 rounded-full flex items-center justify-center mx-auto border-4 border-background">
                                        <i data-lucide="database" class="size-8 text-muted-foreground/30"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black text-foreground uppercase tracking-tight">Stream Silence</h3>
                                        <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em] mt-3 opacity-40">Zero administrative event vectors detected in log partition</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-10 border-t border-border bg-muted/5">
            {{ $logs->links() }}
        </div>
    </div>
    
    <p class="text-center text-[9px] font-black text-muted-foreground uppercase tracking-[0.6em] py-12 opacity-10">STITCH CRYPTO INTEGRITY • LOG_VERIFIED_PERSISTENCE</p>
</div>
