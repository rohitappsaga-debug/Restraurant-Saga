<div 
    x-show="isCommandPaletteOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 z-[100] flex items-start justify-center pt-[15vh] px-4 bg-background/60"
    @keydown.window.ctrl.k.prevent="isCommandPaletteOpen = true"
    @keydown.window.cmd.k.prevent="isCommandPaletteOpen = true"
    @keydown.escape.window="isCommandPaletteOpen = false"
    x-cloak
    x-data="{
        search: '',
        selectedIndex: 0,
        items: [
            { name: 'Dashboard', icon: 'layout-dashboard', route: '{{ route('admin.dashboard') }}', category: 'Navigation' },
            { name: 'Menu Management', icon: 'utensils-crossed', route: '{{ route('admin.menu') }}', category: 'Navigation' },
            { name: 'Categories', icon: 'tag', route: '{{ route('admin.categories') }}', category: 'Navigation' },
            { name: 'Table Management', icon: 'table-2', route: '{{ route('admin.tables') }}', category: 'Navigation' },
            { name: 'Orders', icon: 'clipboard-list', route: '{{ route('admin.orders') }}', category: 'Navigation' },
            { name: 'Sales Reports', icon: 'bar-chart-3', route: '{{ route('admin.reports') }}', category: 'Navigation' },
            { name: 'User Management', icon: 'users', route: '{{ route('admin.users') }}', category: 'Navigation' },
            { name: 'Billing', icon: 'receipt', route: '{{ route('admin.billing') }}', category: 'Navigation' },
            { name: 'System Settings', icon: 'settings', route: '{{ route('admin.settings') }}', category: 'Navigation' },
            { name: 'Audit Logs', icon: 'history', route: '{{ route('admin.audit-logs') }}', category: 'Navigation' },
            { name: 'Suppliers', icon: 'truck', route: '{{ route('admin.suppliers') }}', category: 'Navigation' },
            { name: 'Inventory', icon: 'package', route: '{{ route('admin.inventory') }}', category: 'Navigation' },
            { name: 'Logout System', icon: 'log-out', action: 'logout', category: 'Actions' }
        ],
        get filteredItems() {
            if (!this.search) return this.items;
            const s = this.search.toLowerCase();
            return this.items.filter(item => 
                item.name.toLowerCase().includes(s) || 
                item.category.toLowerCase().includes(s)
            );
        },
        navigate() {
            const item = this.filteredItems[this.selectedIndex];
            if (!item) return;

            if (item.action === 'logout') {
                document.getElementById('logout-form').submit();
                return;
            }
            
            if (typeof Livewire !== 'undefined') {
                Livewire.navigate(item.route);
            } else {
                window.location.href = item.route;
            }
            this.isCommandPaletteOpen = false;
        }
    }"
    @click.away="isCommandPaletteOpen = false"
>
    <!-- Hidden Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <div class="w-full max-w-2xl bg-card rounded-[2rem] shadow-2xl border border-border/60 overflow-hidden flex flex-col max-h-[70vh]">
        <!-- Search Header -->
        <div class="p-6 border-b border-border/50 flex items-center gap-4 bg-muted/20">
            <i data-lucide="search" class="size-6 text-muted-foreground/50"></i>
            <input 
                x-ref="searchInput"
                type="text" 
                x-model="search"
                @keydown.down.prevent="selectedIndex = (selectedIndex + 1) % filteredItems.length"
                @keydown.up.prevent="selectedIndex = (selectedIndex - 1 + filteredItems.length) % filteredItems.length"
                @keydown.enter.prevent="navigate()"
                @input="selectedIndex = 0"
                placeholder="Search pages, orders, or actions... (CTRL + K)"
                class="flex-1 bg-transparent border-none outline-none focus:ring-0 text-lg font-bold text-foreground placeholder:text-muted-foreground/40 placeholder:font-medium"
                x-init="$watch('isCommandPaletteOpen', value => value && $nextTick(() => $refs.searchInput.focus()))"
            >
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-background border border-border rounded-xl shadow-sm">
                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none">ESC</span>
            </div>
        </div>

        <!-- Results List -->
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            <template x-if="filteredItems.length === 0">
                <div class="py-20 text-center">
                    <div class="size-20 bg-muted/30 rounded-3xl flex items-center justify-center mx-auto mb-6 text-muted-foreground/40">
                        <i data-lucide="search-x" class="size-10"></i>
                    </div>
                    <h3 class="text-xl font-bold text-foreground">No results found</h3>
                    <p class="text-muted-foreground font-medium mt-1">Try searching for 'Reports' or 'Logout'</p>
                </div>
            </template>

            <template x-for="(item, index) in filteredItems" :key="item.name">
                <div>
                    <!-- Category Header -->
                    <template x-if="index === 0 || item.category !== filteredItems[index - 1].category">
                        <div class="px-4 pt-6 pb-2 text-[10px] font-black text-muted-foreground/60 uppercase tracking-[0.2em] leading-none">
                            <span x-text="item.category"></span>
                        </div>
                    </template>

                    <!-- Nav Item -->
                    <button 
                        @click="selectedIndex = index; navigate()"
                        @mouseenter="selectedIndex = index"
                        class="w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-200 text-left group"
                        :class="selectedIndex === index ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20' : 'hover:bg-muted/50 text-muted-foreground'"
                    >
                        <div class="flex items-center gap-4">
                            <i :data-lucide="item.icon" class="size-5 transition-transform group-hover:scale-110" :class="selectedIndex === index ? 'text-primary-foreground' : 'text-muted-foreground/50 group-hover:text-primary'"></i>
                            <span class="font-bold text-sm tracking-tight" x-text="item.name"></span>
                        </div>
                        
                        <div x-show="selectedIndex === index" x-transition class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-80">Navigate</span>
                            <i data-lucide="chevron-right" class="size-4 opacity-80"></i>
                        </div>
                    </button>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-muted/20 border-t border-border/50 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="p-1 bg-background border border-border rounded-lg shadow-sm">
                        <i data-lucide="arrow-up" class="size-3 text-muted-foreground/60"></i>
                    </div>
                    <div class="p-1 bg-background border border-border rounded-lg shadow-sm">
                        <i data-lucide="arrow-down" class="size-3 text-muted-foreground/60"></i>
                    </div>
                    <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Navigate</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="px-2 py-1 bg-background border border-border rounded-lg shadow-sm text-[10px] font-black text-muted-foreground leading-none">
                        ENTER
                    </div>
                    <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Select</span>
                </div>
            </div>
            
            <div class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">
                Stitch OS <span class="text-primary">v2.1</span>
            </div>
        </div>
    </div>

    <!-- Script to initialize icons after Alpine renders -->
    <script>
        document.addEventListener('alpine:init', () => {
            $watch('isCommandPaletteOpen', value => {
                if (value) {
                    setTimeout(() => lucide.createIcons(), 10);
                }
            });
        });
        
        // Ensure icons are created on initial render if open
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(var(--color-border-rgb), 0.5);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--color-border-rgb), 0.8);
        }
    </style>
</div>
