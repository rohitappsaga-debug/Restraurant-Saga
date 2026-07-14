<div class="max-w-5xl mx-auto space-y-8 pb-32 px-4" x-data="{ activeTab: 'general' }">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Settings</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Configure system preferences and defaults</p>
        </div>
        <button 
            wire:click="save"
            class="h-14 px-8 bg-primary text-white shadow-lg shadow-primary/20 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center gap-3"
        >
            <i data-lucide="save" class="size-5"></i>
            Save Settings
        </button>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <!-- Display Settings -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3 mb-8">
                <i data-lucide="moon" class="size-5 text-orange-500"></i>
                Display Settings
            </h2>
            <div 
                class="flex items-center justify-between p-6 bg-muted/20 rounded-2xl border border-border/50"
            >
                <div>
                    <span class="block text-sm font-bold text-foreground">Dark Mode</span>
                    <p class="text-[10px] text-muted-foreground font-medium mt-0.5">Toggle application-wide dark theme</p>
                </div>
                <button 
                    type="button" 
                    @click="$store.theme.toggle()"
                    role="switch"
                    :aria-checked="$store.theme.current === 'dark'"
                    aria-label="Dark mode"
                    class="relative z-10 inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-4 border-transparent transition-colors duration-200 focus:outline-none"
                    :class="$store.theme.current === 'dark' ? 'bg-primary' : 'bg-muted'"
                >
                    <span 
                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out"
                        :class="$store.theme.current === 'dark' ? 'translate-x-6' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

        </div>



        <!-- Restaurant Information -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                <i data-lucide="building" class="size-5 text-blue-500"></i>
                Restaurant Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-restaurant_name">Restaurant Name</label>
                    <input type="text" wire:model="restaurant_name" id="setting-restaurant_name" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium tracking-tight text-foreground">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-restaurant_address">Restaurant Address</label>
                    <input type="text" wire:model="restaurant_address" id="setting-restaurant_address" placeholder="Full address for bill header" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium text-foreground">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-gst_no">GST Number</label>
                    <input type="text" wire:model="gst_no" id="setting-gst_no" placeholder="e.g. 29ABCDE1234F1Z5" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium uppercase tracking-widest text-foreground">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-currency">Currency Symbol</label>
                    <input type="text" wire:model="currency" id="setting-currency" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-black text-center text-foreground">
                </div>
            </div>
        </div>

        <!-- Tax Configuration -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                <i data-lucide="percent" class="size-5 text-rose-500"></i>
                Tax Configuration
            </h2>
            <div class="p-6 bg-muted/20 rounded-2xl border border-border/50 flex flex-col gap-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-sm font-bold text-foreground">Enable Tax Calculation</span>
                        <p class="text-[10px] text-muted-foreground font-medium mt-0.5">Toggle whether GST/VAT should be applied to orders</p>
                    </div>
                    <button 
                        type="button" 
                        @click="$wire.set('tax_enabled', !@js($tax_enabled))"
                        role="switch"
                        aria-checked="{{ $tax_enabled ? 'true' : 'false' }}"
                        aria-label="Enable tax calculation"
                        class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-4 border-transparent transition-colors duration-200 focus:outline-none {{ $tax_enabled ? 'bg-primary' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out {{ $tax_enabled ? 'translate-x-6' : 'translate-x-0' }}"></span>
                    </button>
                </div>
                
                <div class="space-y-2 {{ !$tax_enabled ? 'opacity-30 pointer-events-none' : '' }}">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-tax_rate">Tax Rate (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="tax_rate" id="setting-tax_rate" class="h-14 w-full pl-5 pr-12 rounded-xl bg-background border border-border/50 focus:ring-4 focus:ring-primary/10 transition-all outline-none font-black text-lg text-foreground">
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-sm font-bold text-muted-foreground">%</span>
                    </div>
                    <p class="text-[9px] text-muted-foreground font-bold uppercase tracking-widest mt-1">Applied to all orders. Current levy: {{ $tax_rate }}%</p>
                </div>
            </div>
        </div>

        <!-- Discount Presets -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                <i data-lucide="tag" class="size-5 text-emerald-500"></i>
                Discount Presets
            </h2>
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1">Quick Discount Buttons</label>
                    <p class="text-[10px] text-muted-foreground font-medium">Add percentage shortcuts that staff can quickly apply to orders</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @foreach($discount_presets as $v)
                        <div class="h-12 pl-4 pr-1.5 bg-background border border-border/50 rounded-xl flex items-center gap-3 shadow-sm group">
                            <span class="text-xs font-black text-primary">{{ $v }}%</span>
                            <button wire:click="removeDiscountPreset({{ $v }})" aria-label="Remove {{ $v }}% discount preset" class="size-9 rounded-lg bg-muted/50 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center">
                                <i data-lucide="x" class="size-3.5"></i>
                            </button>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-2">
                        <input 
                            type="number" 
                            wire:model="new_discount_preset"
                            aria-label="New discount percentage"
                            placeholder="Add Discount %" 
                            class="h-10 w-32 px-4 rounded-xl bg-muted/30 border border-border/50 text-[10px] font-black uppercase outline-none focus:ring-2 focus:ring-primary/10"
                        >
                        <button wire:click="addDiscountPreset" class="h-10 px-4 bg-muted hover:bg-primary/20 text-foreground rounded-xl text-[10px] font-black uppercase transition-all flex items-center gap-2">
                            <i data-lucide="plus" class="size-3.5"></i>
                            Add Preset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Printer & Business Hours (Side by Side) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
                <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                    <i data-lucide="printer" class="size-5 text-indigo-500"></i>
                    Printer Configuration
                </h2>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-sm font-bold text-foreground">Enable Printing</span>
                            <p class="text-[10px] text-muted-foreground font-medium mt-0.5">Allow printing bills and kitchen orders</p>
                        </div>
                        <button 
                            type="button" 
                            @click="$wire.set('printer_enabled', !@js($printer_enabled))"
                        role="switch"
                        aria-checked="{{ $printer_enabled ? 'true' : 'false' }}"
                        aria-label="Enable printing"
                            class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-4 border-transparent transition-colors duration-200 focus:outline-none {{ $printer_enabled ? 'bg-indigo-500' : 'bg-muted' }}"
                        >
                            <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out {{ $printer_enabled ? 'translate-x-6' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                    <div class="space-y-2 {{ !$printer_enabled ? 'opacity-30 pointer-events-none' : '' }}">
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-printer_name">Printer Name</label>
                        <input type="text" wire:model="printer_name" id="setting-printer_name" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none font-medium text-foreground">
                    </div>
                </div>
            </div>

            <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
                <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                    <i data-lucide="clock" class="size-5 text-amber-500"></i>
                    Business Hours
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-open_time">Opening Time</label>
                        <input type="text" wire:model="open_time" id="setting-open_time" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 font-bold text-sm tracking-widest outline-none focus:ring-4 focus:ring-primary/10 text-foreground">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-close_time">Closing Time</label>
                        <input type="text" wire:model="close_time" id="setting-close_time" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 font-bold text-sm tracking-widest outline-none focus:ring-4 focus:ring-primary/10 text-foreground">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                <i data-lucide="bell" class="size-5 text-rose-500"></i>
                Notifications
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-5 bg-muted/20 rounded-2xl border border-border/50 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-black text-foreground uppercase tracking-tight">Order Notifications</span>
                        <p class="text-[9px] text-muted-foreground font-medium mt-1">Notify when new orders are placed</p>
                    </div>
                    <button 
                        type="button"
                        @click="$wire.set('order_notifications', !@js($order_notifications))"
                        role="switch"
                        aria-checked="{{ $order_notifications ? 'true' : 'false' }}"
                        aria-label="Order notifications"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $order_notifications ? 'bg-primary' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out {{ $order_notifications ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
                <div class="p-5 bg-muted/20 rounded-2xl border border-border/50 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-black text-foreground uppercase tracking-tight">Payment Notifications</span>
                        <p class="text-[9px] text-muted-foreground font-medium mt-1">Notify when payments are received</p>
                    </div>
                    <button 
                        type="button"
                        @click="$wire.set('payment_notifications', !@js($payment_notifications))"
                        role="switch"
                        aria-checked="{{ $payment_notifications ? 'true' : 'false' }}"
                        aria-label="Payment notifications"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $payment_notifications ? 'bg-primary' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out {{ $payment_notifications ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
                <div class="p-5 bg-muted/20 rounded-2xl border border-border/50 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-black text-foreground uppercase tracking-tight">Low Stock Alerts</span>
                        <p class="text-[9px] text-muted-foreground font-medium mt-1">Alert when items are running out</p>
                    </div>
                    <button 
                        type="button"
                        @click="$wire.set('low_stock_alerts', !@js($low_stock_alerts))"
                        role="switch"
                        aria-checked="{{ $low_stock_alerts ? 'true' : 'false' }}"
                        aria-label="Low stock alerts"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $low_stock_alerts ? 'bg-primary' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out {{ $low_stock_alerts ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment & Receipt -->
        <div class="p-8 bg-card border border-border/50 rounded-[2rem] shadow-sm space-y-8">
            <h2 class="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-3">
                <i data-lucide="credit-card" class="size-5 text-purple-500"></i>
                Payment & Receipt
            </h2>
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1">Enabled Payment Methods</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach(['CASH', 'CARD', 'UPI'] as $method)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" checked class="peer sr-only">
                                <div class="size-6 rounded-lg border border-border flex items-center justify-center transition-all peer-checked:bg-primary peer-checked:border-primary">
                                    <i data-lucide="check" class="size-3 text-white"></i>
                                </div>
                                <span class="text-xs font-bold text-foreground opacity-60 group-hover:opacity-100 transition-opacity">{{ $method }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest ml-1" for="setting-receipt_footer">Receipt Footer Message</label>
                    <input type="text" wire:model="receipt_footer" id="setting-receipt_footer" class="h-14 w-full px-5 rounded-xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none font-medium text-foreground">
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="p-10 bg-card/60 backdrop-blur-md border border-border/50 rounded-[2.5rem] relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="cpu" class="size-48 text-foreground"></i>
            </div>
            <div class="flex items-center justify-between mb-10 relative z-10">
                <h3 class="text-xs font-black text-foreground uppercase tracking-[0.2em] flex items-center gap-3">
                    <i data-lucide="info" class="size-5 text-muted-foreground"></i>
                    System Information
                </h3>
                <div class="flex items-center gap-2 px-3 py-1 bg-emerald-500/10 rounded-full border border-emerald-500/20">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">Healthy</span>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-10 relative z-10">
                <div class="space-y-1">
                    <span class="block text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1 leading-none">System Version</span>
                    <span class="text-sm font-black text-foreground">{{ $system['version'] }}</span>
                </div>
                <div class="space-y-1">
                    <span class="block text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1 leading-none">Last Backup</span>
                    <span class="text-sm font-black text-foreground tracking-tighter">{{ $system['last_backup'] }}</span>
                </div>
                <div class="space-y-1">
                    <span class="block text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1 leading-none">DB Driver</span>
                    <span class="text-sm font-black text-emerald-700 dark:text-emerald-400 uppercase">{{ $system['db_driver'] }}</span>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end pr-2">
            <button wire:click="save" class="h-16 px-12 bg-primary text-white shadow-xl shadow-primary/20 rounded-2xl font-black text-sm uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
                Save Changes
            </button>
        </div>
    </div>

    @script
    <script>
        // Local state synchronization (silent)
        $wire.on('notify', () => {
            // Notifications and other small UI state changes are handled by the global layout hook
        });
    </script>
    @endscript
</div>
