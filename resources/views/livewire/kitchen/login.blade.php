<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-zinc-950 text-zinc-100">
    <!-- Guaranteed Background Layer -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <img
            src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?q=80&w=1920&auto=format&fit=crop"
            alt=""
            class="absolute inset-0 w-full h-full object-cover opacity-50"
            loading="eager"
        />
        <!-- Dark Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/70 to-emerald-950/60 backdrop-blur-[3px]"></div>

        <!-- Specialized Kitchen Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-emerald-900/15 rounded-full blur-[150px]"></div>
    </div>

    <div class="w-full max-w-md z-10 relative">
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-emerald-600 rounded-[2rem] mb-6 shadow-2xl shadow-emerald-900/50 ring-4 ring-emerald-900/30 group transition-transform hover:scale-110 duration-500">
                <i data-lucide="chef-hat" class="size-12 text-white"></i>
            </div>
            <h1 class="text-4xl font-black text-white mb-2 tracking-tighter">{{ $restaurantName }}</h1>
            <p class="text-emerald-400 font-black tracking-[0.3em] uppercase text-xs">Back of House Access</p>
        </div>

        <div class="p-8 shadow-2xl bg-zinc-900/95 backdrop-blur-xl border border-zinc-800 rounded-[2.5rem] ring-1 ring-white/10 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-emerald-500 to-transparent opacity-50"></div>
            
            <form wire:submit="login" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest ml-1">Access ID / Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-zinc-500 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="kitchen@restaurant.com"
                            class="w-full pl-12 pr-4 h-14 bg-black/40 border border-zinc-700 rounded-2xl text-zinc-100 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none font-medium"
                            required
                        />
                    </div>
                    @error('email') <span class="text-xs text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2" x-data="{ showPassword: false }">
                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest ml-1">Passcode</label>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-zinc-500 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input
                            x-bind:type="showPassword ? 'text' : 'password'"
                            wire:model="password"
                            placeholder="••••••••"
                            class="w-full pl-12 pr-12 h-14 bg-black/40 border border-zinc-700 rounded-2xl text-zinc-100 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none font-medium"
                            required
                        />
                        <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-emerald-500 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 rounded-md">
                            <i x-show="!showPassword" data-lucide="eye" class="size-5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="size-5" style="display: none;"></i>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full h-14 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-emerald-900/30 transition-all active:scale-[0.98] flex items-center justify-center gap-3 relative group overflow-hidden"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove class="relative z-10 flex items-center gap-2">
                        Access Kitchen Display
                        <i data-lucide="arrow-right" class="size-4 group-hover:translate-x-1 transition-transform"></i>
                    </span>
                    <span wire:loading class="relative z-10">Authenticating...</span>
                    
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-zinc-800 text-center">
                <div class="flex items-center justify-center gap-2 text-zinc-500">
                    <i data-lucide="shield-check" class="size-4"></i>
                    <p class="text-[10px] font-bold uppercase tracking-widest">Authorized Personnel Only</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="/" class="text-xs font-bold text-zinc-500 hover:text-emerald-400 transition-colors uppercase tracking-widest flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="size-3"></i>
                Back to Portal
            </a>
        </div>
    </div>
</div>
