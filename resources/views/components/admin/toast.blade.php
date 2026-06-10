<div 
    x-data="{ 
        toasts: [],
        showToast(message, type = 'success') {
            if (!message || message.trim() === '') return;
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.removeToast(id);
            }, 5000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @notify.window="
        let payload = $event.detail;
        if (typeof payload === 'string') {
            showToast(payload, 'success');
        } else if (payload) {
            // Livewire 3 often wraps the single argument in an array or object
            let message = payload.message || (Array.isArray(payload) ? payload[0]?.message : null);
            let type = payload.type || (Array.isArray(payload) ? payload[0]?.type : 'success');
            if (message) showToast(message, type);
        }
    "
    class="fixed bottom-8 right-8 z-[200] flex flex-col gap-3 pointer-events-none"
    x-init="
        window.showToast = (m, t) => showToast(m, t);
        $watch('toasts', () => {
            $nextTick(() => {
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        });
    "
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 scale-90"
            class="pointer-events-auto bg-card border border-border/60 shadow-2xl rounded-2xl px-5 py-4 flex items-center gap-4 min-w-[320px] backdrop-blur-xl"
        >
            <div :class="{
                'bg-emerald-500/10 text-emerald-500': toast.type === 'success',
                'bg-rose-500/10 text-rose-500': toast.type === 'error',
                'bg-amber-500/10 text-amber-500': toast.type === 'warning',
                'bg-blue-500/10 text-blue-500': toast.type === 'info',
            }" class="p-2 rounded-xl">
                <template x-if="toast.type === 'success'"><i data-lucide="check-circle-2" class="size-5"></i></template>
                <template x-if="toast.type === 'error'"><i data-lucide="alert-circle" class="size-5"></i></template>
                <template x-if="toast.type === 'warning'"><i data-lucide="alert-triangle" class="size-5"></i></template>
                <template x-if="toast.type === 'info'"><i data-lucide="info" class="size-5"></i></template>
            </div>
            
            <div class="flex-1">
                <p x-text="toast.message" class="text-sm font-bold text-foreground leading-tight"></p>
            </div>

            <button @click="removeToast(toast.id)" class="text-muted-foreground hover:text-foreground transition-colors p-1">
                <i data-lucide="x" class="size-4"></i>
            </button>
            
            <div x-init="lucide.createIcons()" class="hidden"></div>
        </div>
    </template>
</div>
