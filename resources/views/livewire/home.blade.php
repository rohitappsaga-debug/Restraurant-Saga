<div class="min-h-screen flex flex-col items-center justify-center p-6 transition-colors duration-500 relative overflow-hidden bg-background">
    <!-- Guaranteed Background Layer -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <img
            src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1920&auto=format&fit=crop"
            alt=""
            class="absolute inset-0 w-full h-full object-cover opacity-60 dark:opacity-40"
            loading="eager"
        />
        <!-- Overlay -->
        <div class="absolute inset-0 bg-background/40 backdrop-blur-[2px]"></div>

        <!-- Decorative Gradients -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-amber-500/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-blue-500/10 rounded-full blur-[100px]"></div>
    </div>

    <!-- Theme Toggle -->
    <div class="absolute top-8 right-8 z-50">
        <button
            onclick="toggleTheme()"
            class="size-14 bg-card/50 backdrop-blur-xl shadow-glow-sm border border-border/50 rounded-full flex items-center justify-center hover:scale-105 active:scale-95 transition-all text-muted-foreground hover:text-foreground group"
        >
            <i data-lucide="sun" class="dark:hidden text-amber-500 size-6 group-hover:rotate-45 transition-transform duration-500"></i>
            <i data-lucide="moon" class="hidden dark:block text-blue-400 size-6 group-hover:-rotate-12 transition-transform duration-500"></i>
        </button>
    </div>

    <div class="w-full max-w-6xl z-10 relative">
        <div class="text-center mb-20 space-y-6">
            <h1 class="text-6xl font-black tracking-tighter text-foreground drop-shadow-xl uppercase">
                {{ $restaurantName }}
            </h1>
            <p class="text-xl text-muted-foreground max-w-2xl mx-auto font-medium">
                Welcome back. Please select your role to access the workspace terminal.
            </p>
            <div class="flex items-center justify-center gap-3 mt-8">
                <div class="relative flex items-center justify-center">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-20 animate-ping"></span>
                    <span class="relative flex h-3 w-3 rounded-full bg-emerald-500 shadow-glow shadow-emerald-500/50"></span>
                </div>
                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em]">System Operational</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 px-4">
            <!-- Waiter Card -->
            <a href="/waiter/login" wire:navigate class="group relative p-10 bg-card/60 backdrop-blur-xl rounded-[3rem] border border-border/50 shadow-glow-sm hover:shadow-glow hover:border-amber-500/50 transition-all duration-500 text-left overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/0 via-amber-500/0 to-amber-500/[0.03] group-hover:to-amber-500/[0.08] transition-all duration-700"></div>
                
                <div class="size-20 bg-amber-500/10 rounded-[1.75rem] border border-amber-500/20 flex items-center justify-center mb-8 text-amber-500 group-hover:scale-110 group-hover:rotate-3 transition-all duration-500">
                    <i data-lucide="users" class="size-10"></i>
                </div>
                
                <h2 class="text-3xl font-black text-foreground mb-4 group-hover:text-amber-500 transition-colors tracking-tight uppercase">Waiter / Staff</h2>
                <p class="text-muted-foreground leading-relaxed text-sm font-medium h-20 opacity-80">
                    Table management, order taking, and bill processing for service staff.
                </p>
                
                <div class="mt-10 flex items-center text-amber-500 font-black text-[10px] uppercase tracking-[0.3em] opacity-40 group-hover:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0">
                    Access Portal <i data-lucide="arrow-right" class="ml-2 size-5"></i>
                </div>
            </a>

            <!-- Admin Card -->
            <a href="/admin/login" wire:navigate class="group relative p-10 bg-card/60 backdrop-blur-xl rounded-[3rem] border border-border/50 shadow-glow-sm hover:shadow-glow hover:border-indigo-500/50 transition-all duration-500 text-left overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/0 via-indigo-500/0 to-indigo-500/[0.03] group-hover:to-indigo-500/[0.08] transition-all duration-700"></div>
                
                <div class="size-20 bg-indigo-500/10 rounded-[1.75rem] border border-indigo-500/20 flex items-center justify-center mb-8 text-indigo-500 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-500">
                    <i data-lucide="shield-check" class="size-10"></i>
                </div>
                
                <h2 class="text-3xl font-black text-foreground mb-4 group-hover:text-indigo-500 transition-colors tracking-tight uppercase">Admin / Manager</h2>
                <p class="text-muted-foreground leading-relaxed text-sm font-medium h-20 opacity-80">
                    Full system control, menu management, analytics, and staff settings.
                </p>
                
                <div class="mt-10 flex items-center text-indigo-500 font-black text-[10px] uppercase tracking-[0.3em] opacity-40 group-hover:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0">
                    Access Dashboard <i data-lucide="arrow-right" class="ml-2 size-5"></i>
                </div>
            </a>

            <!-- Kitchen Card -->
            <a href="/kitchen/login" wire:navigate class="group relative p-10 bg-card/60 backdrop-blur-xl rounded-[3rem] border border-border/50 shadow-glow-sm hover:shadow-glow hover:border-emerald-500/50 transition-all duration-500 text-left overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 via-emerald-500/0 to-emerald-500/[0.03] group-hover:to-emerald-500/[0.08] transition-all duration-700"></div>
                
                <div class="size-20 bg-emerald-500/10 rounded-[1.75rem] border border-emerald-500/20 flex items-center justify-center mb-8 text-emerald-500 group-hover:scale-110 group-hover:rotate-2 transition-all duration-500">
                    <i data-lucide="flame" class="size-10"></i>
                </div>
                
                <h2 class="text-3xl font-black text-foreground mb-4 group-hover:text-emerald-500 transition-colors tracking-tight uppercase">Kitchen Staff</h2>
                <p class="text-muted-foreground leading-relaxed text-sm font-medium h-20 opacity-80">
                    Real-time order display, status updates, and cooking queue management.
                </p>
                
                <div class="mt-10 flex items-center text-emerald-500 font-black text-[10px] uppercase tracking-[0.3em] opacity-40 group-hover:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0">
                    Access KDS <i data-lucide="arrow-right" class="ml-2 size-5"></i>
                </div>
            </a>
        </div>
    </div>

    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('rms_guest_theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('rms_guest_theme', 'dark');
            }
        }
    </script>
</div>
