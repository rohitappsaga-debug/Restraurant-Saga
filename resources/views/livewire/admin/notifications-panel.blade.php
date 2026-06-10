<div 
    x-show="isNotificationsOpen"
    x-cloak
    class="fixed inset-0 z-[100] flex justify-end overflow-hidden"
    aria-labelledby="slide-over-title" 
    role="dialog" 
    aria-modal="true"
    @keydown.escape.window="isNotificationsOpen = false"
>
    <!-- Backdrop -->
    <div 
        x-show="isNotificationsOpen"
        x-transition:enter="ease-in-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-background/60 transition-opacity" 
        @click="isNotificationsOpen = false"
        aria-hidden="true"
    ></div>

    <!-- Panel -->
    <div 
        x-show="isNotificationsOpen"
        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="relative w-screen max-w-md pointer-events-auto"
    >
        <div class="flex flex-col h-full bg-card shadow-2xl border-l border-border/40">
            <!-- Header -->
            <div class="px-6 py-8 border-b border-border/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button 
                            @click="isNotificationsOpen = false"
                            class="p-2 -ml-2 rounded-xl text-muted-foreground hover:text-foreground hover:bg-muted transition-all active:scale-90"
                        >
                            <i data-lucide="chevron-right" class="size-6"></i>
                        </button>
                        <div>
                            <h2 class="text-2xl font-black text-foreground tracking-tight uppercase tracking-tighter" id="slide-over-title">Notifications</h2>
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] mt-1">
                                {{ $unreadCount }} Unread Signals detected
                            </p>
                        </div>
                    </div>
                    @if($notifications->isNotEmpty())
                        <button 
                            wire:click="clearAll"
                            wire:confirm="Permanent erasure of all notification logs. Proceed?"
                            class="p-2 rounded-xl text-muted-foreground hover:text-rose-500 hover:bg-rose-500/10 transition-all active:scale-95 group"
                            title="Clear All"
                        >
                            <i data-lucide="trash-2" class="size-5 group-hover:animate-bounce"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto no-scrollbar py-6 px-4 space-y-4">
                @forelse($notifications as $notification)
                    <div 
                        wire:key="notif-{{ $notification->id }}"
                        @click="$wire.markAsRead('{{ $notification->id }}')"
                        class="group relative bg-card border {{ $notification->read ? 'border-border/40 opacity-60' : 'border-primary/30 bg-primary/5' }} rounded-[2rem] p-6 transition-all hover:shadow-glow-sm hover:scale-[1.02] cursor-pointer overflow-hidden"
                    >
                        <!-- Dynamic Background Glow -->
                        @php
                            $theme = match($notification->type->value) {
                                'order' => 'blue',
                                'payment' => 'emerald',
                                'alert' => 'rose',
                                default => 'slate'
                            };
                            $icon = match($notification->type->value) {
                                'order' => 'shopping-cart',
                                'payment' => 'banknote',
                                'alert' => 'alert-triangle',
                                default => 'bell'
                            };
                        @endphp
                        
                        <div class="absolute -right-4 -top-4 size-24 bg-{{ $theme }}-500/5 rounded-full blur-2xl group-hover:bg-{{ $theme }}-500/10 transition-all"></div>

                        <div class="flex gap-5 relative z-10">
                            <!-- Icon Housing -->
                            <div class="shrink-0">
                                <div class="size-14 rounded-2xl bg-{{ $theme }}-500/10 flex items-center justify-center text-{{ $theme }}-500 border border-{{ $theme }}-500/20 shadow-sm relative group-hover:rotate-6 transition-transform">
                                    <i data-lucide="{{ $icon }}" class="size-6"></i>
                                    @if(!$notification->read)
                                        <span class="absolute -top-1 -right-1 size-3 bg-{{ $theme }}-500 border-2 border-background rounded-full"></span>
                                    @endif
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-black text-{{ $theme }}-500 uppercase tracking-widest">{{ $notification->type->value }}</span>
                                    <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-tight">{{ $notification->created_at->diffForHumans(null, true) }}</span>
                                </div>
                                <p class="text-sm font-bold text-foreground leading-tight group-hover:text-{{ $theme }}-600 transition-colors uppercase tracking-tight">
                                    {{ $notification->message }}
                                </p>
                            </div>

                            <!-- Delete Trigger -->
                            <button 
                                wire:click.stop="delete('{{ $notification->id }}')"
                                class="opacity-0 group-hover:opacity-100 p-2 rounded-lg text-muted-foreground/40 hover:text-rose-500 hover:bg-rose-500/10 transition-all shrink-0"
                            >
                                <i data-lucide="x" class="size-4"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-20 text-center space-y-6 opacity-40">
                        <div class="size-24 bg-muted/30 rounded-full flex items-center justify-center mx-auto grayscale">
                            <i data-lucide="bell-off" class="size-10 text-muted-foreground/40"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-foreground uppercase tracking-tight">All Caught Up</h3>
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-2">Zero administrative signals pending</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Footer -->
            @if($notifications->isNotEmpty())
            <div class="p-6 border-t border-border/50 bg-muted/20">
                <button 
                    wire:click="markAllAsRead"
                    class="w-full py-4 bg-background border border-border/60 rounded-2xl text-[11px] font-black text-muted-foreground uppercase tracking-widest hover:bg-muted hover:text-foreground transition-all shadow-sm active:scale-95 flex items-center justify-center gap-3"
                >
                    <i data-lucide="check-check" class="size-4 text-primary"></i>
                    Acknowledge All Signals
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
