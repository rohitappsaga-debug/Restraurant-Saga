<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-background">
    <!-- Guaranteed Background Layer -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <img
            src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=1920&auto=format&fit=crop"
            alt=""
            class="absolute inset-0 w-full h-full object-cover opacity-60 dark:opacity-30 blur-sm scale-105"
            loading="eager"
        />
        <!-- Premium Overlay -->
        <div class="absolute inset-0 bg-background/30 backdrop-blur-[2px]"></div>

        <!-- Subtle Accents -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-900/5 rounded-full blur-[150px]"></div>
    </div>

    <div class="w-full max-w-[440px] z-10 relative">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-2xl mb-6 shadow-glow shadow-primary/40 transition-transform duration-300 hover:scale-105">
                <i data-lucide="utensils-crossed" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2 tracking-tight">
                RestaurantSaga
            </h1>
            <p class="text-muted-foreground text-sm font-medium">Internal Restaurant Portal</p>
        </div>

        <div class="bg-card/95 border border-border/50 rounded-2xl shadow-2xl p-8 mb-8 backdrop-blur-xl">
            <form wire:submit="login" class="space-y-6">
                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-xl text-xs text-red-500 font-medium">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-foreground/90 pl-1">Email Address</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/40 group-focus-within:text-primary transition-colors"></i>
                        <input
                            id="email"
                            type="email"
                            wire:model="email"
                            placeholder="name@restaurant.com"
                            class="w-full h-12 pl-12 pr-4 bg-muted/30 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            required
                        />
                    </div>
                </div>

                <div class="space-y-2" x-data="{ showPassword: false }">
                    <label for="password" class="text-sm font-semibold text-foreground/90 pl-1">Password</label>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/40 group-focus-within:text-primary transition-colors"></i>
                        <input
                            id="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            wire:model="password"
                            placeholder="........"
                            class="w-full h-12 pl-12 pr-12 bg-muted/30 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            required
                        />
                        <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground/60 hover:text-foreground transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded-md">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" style="display: none;"></i>
                        </button>
                    </div>
                </div>

                <!-- Role Selector -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-foreground/90 pl-1">Select Role</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            wire:click="$set('selectedRole', 'waiter')"
                            class="relative p-4 rounded-xl border transition-all duration-300 flex flex-col items-center justify-center gap-2 group overflow-hidden {{ $selectedRole === 'waiter' ? 'border-primary/50 bg-primary/10 shadow-glow shadow-primary/20' : 'border-border bg-muted/20 hover:bg-muted/40' }}"
                        >
                            <i data-lucide="hand-platter" class="size-6 {{ $selectedRole === 'waiter' ? 'text-primary' : 'text-muted-foreground' }}"></i>
                            <span class="text-xs font-bold tracking-wide {{ $selectedRole === 'waiter' ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground' }}">Waiter</span>
                            @if($selectedRole === 'waiter')
                                <div class="absolute inset-0 border-2 border-primary/50 rounded-xl"></div>
                            @endif
                        </button>
                        <button
                            type="button"
                            wire:click="$set('selectedRole', 'manager')"
                            class="relative p-4 rounded-xl border transition-all duration-300 flex flex-col items-center justify-center gap-2 group overflow-hidden {{ $selectedRole === 'manager' ? 'border-primary/50 bg-primary/10 shadow-glow shadow-primary/20' : 'border-border bg-muted/20 hover:bg-muted/40' }}"
                        >
                            <i data-lucide="briefcase-business" class="size-6 {{ $selectedRole === 'manager' ? 'text-primary' : 'text-muted-foreground' }}"></i>
                            <span class="text-xs font-bold tracking-wide {{ $selectedRole === 'manager' ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground' }}">Manager</span>
                            @if($selectedRole === 'manager')
                                <div class="absolute inset-0 border-2 border-primary/50 rounded-xl"></div>
                            @endif
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-[0_8px_20px_rgba(92,92,252,0.3)] transition-all flex items-center justify-center gap-2 group disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Login</span>
                    <span wire:loading.flex class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </button>
            </form>
        </div>

        <div class="mt-6 text-center">
            <a href="/" wire:navigate class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2 rounded-xl text-muted-foreground hover:text-foreground text-sm font-medium transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Selection
            </a>
        </div>
    </div>

    @script
    <script>
        lucide.createIcons();
    </script>
    @endscript
</div>
