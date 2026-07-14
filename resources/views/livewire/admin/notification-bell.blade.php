<div wire:poll.30s>
    <button
        @click="isNotificationsOpen = !isNotificationsOpen"
        class="p-2.5 rounded-xl text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-all relative active:scale-90 group"
        aria-label="Notifications{{ $unreadCount > 0 ? ', ' . $unreadCount . ' unread' : '' }}"
    >
        <i data-lucide="bell" class="size-5 group-hover:rotate-12 transition-transform"></i>
        @if($unreadCount > 0)
            <span class="absolute top-2.5 right-2.5 size-4 bg-rose-500 border-2 border-white rounded-full flex items-center justify-center">
                <span class="text-[8px] font-black text-white leading-none">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            </span>
            <span class="absolute top-2.5 right-2.5 size-4 bg-rose-500 rounded-full animate-ping opacity-20"></span>
        @endif
    </button>
</div>
