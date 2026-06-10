<div class="space-y-8" x-data="{ showAddDialog: @entangle('showAddDialog') }">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tight uppercase tracking-tighter">Logistics Network</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">External procurement partners and global supply chain directory</p>
        </div>
        <button 
            @click="$wire.openCreate()"
            class="h-14 px-8 bg-foreground text-background shadow-glow rounded-full font-black text-xs uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all flex items-center gap-3 border-0"
        >
            <i data-lucide="handshake" class="size-5 text-amber-500"></i>
            Affiliate Partner
        </button>
    </div>

    <!-- Partner Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 px-2">
        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="network" class="size-32"></i>
            </div>
            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mb-3 relative z-10">Active Nodes</p>
            <div class="text-foreground font-black text-3xl tracking-tighter relative z-10">{{ $suppliers->total() }} <span class="text-xs text-muted-foreground uppercase tracking-widest ml-1 opacity-40">Partners</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="truck" class="size-32 text-amber-500"></i>
            </div>
            <p class="text-[10px] font-black text-amber-500 uppercase tracking-[0.3em] mb-3 relative z-10">Procurement Flow</p>
            <div class="text-amber-500 font-black text-3xl tracking-tighter relative z-10">12 <span class="text-xs opacity-40 uppercase tracking-widest ml-1">Orders</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="banknote" class="size-32 text-blue-500"></i>
            </div>
            <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.3em] mb-3 relative z-10">Network Spend</p>
            <div class="text-blue-500 font-black text-3xl tracking-tighter relative z-10">$8.4k <span class="text-xs opacity-40 uppercase tracking-widest ml-1">MTD</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="shield-check" class="size-32 text-purple-500"></i>
            </div>
            <p class="text-[10px] font-black text-purple-500 uppercase tracking-[0.3em] mb-3 relative z-10">Trust Rating</p>
            <div class="text-purple-500 font-black text-3xl tracking-tighter relative z-10">98% <span class="text-xs opacity-40 uppercase tracking-widest ml-1">AVG</span></div>
        </div>
    </div>

    <!-- Search/Filter -->
    <div class="p-3 bg-muted/30 backdrop-blur-md border border-border rounded-[2.5rem] shadow-glow-sm mx-2">
        <div class="relative max-w-xl">
            <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40"></i>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Identify partner by name, contact or logistics email..."
                class="pl-14 h-14 w-full bg-background/50 border border-border/50 rounded-[1.75rem] text-sm font-black uppercase tracking-tight focus:bg-background focus:ring-4 focus:ring-amber-500/10 transition-all outline-none placeholder:text-muted-foreground/30"
            />
        </div>
    </div>

    <!-- Partners Matrix -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-2">
        @foreach($suppliers as $supplier)
            <div 
                wire:key="supplier-{{ $supplier->id }}"
                class="bg-card border border-border rounded-[3.5rem] shadow-glow-sm hover:shadow-glow transition-all group relative overflow-hidden p-8"
            >
                <!-- Background Ornament -->
                <div class="absolute -right-10 -top-10 size-40 bg-amber-500/[0.03] rounded-full blur-3xl group-hover:bg-amber-500/[0.08] transition-all"></div>
                
                <div class="flex justify-between items-start mb-8 relative z-10">
                    <div class="flex items-center gap-5">
                        <div class="size-16 rounded-[1.75rem] bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-black text-xl shadow-glow shadow-amber-500/20 group-hover:scale-110 transition-transform">
                            {{ substr($supplier->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-black text-foreground uppercase tracking-tighter truncate">{{ $supplier->name }}</h3>
                            @if($supplier->contact_name)
                                <p class="text-[9px] font-black text-amber-500 uppercase tracking-[0.2em] mt-1 flex items-center gap-2">
                                    <i data-lucide="user" class="size-3.5"></i>
                                    {{ $supplier->contact_name }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4 relative z-10">
                    @if($supplier->email)
                        <a href="mailto:{{ $supplier->email }}" class="flex items-center gap-4 py-3 px-5 rounded-2xl bg-muted/30 border border-border/50 text-[10px] font-black text-muted-foreground uppercase tracking-widest hover:bg-muted hover:text-foreground transition-all">
                            <i data-lucide="mail" class="size-5 opacity-40"></i>
                            {{ $supplier->email }}
                        </a>
                    @endif
                    @if($supplier->phone)
                        <a href="tel:{{ $supplier->phone }}" class="flex items-center gap-4 py-3 px-5 rounded-2xl bg-muted/30 border border-border/50 text-[10px] font-black text-muted-foreground uppercase tracking-widest hover:bg-muted hover:text-foreground transition-all">
                            <i data-lucide="phone" class="size-5 opacity-40"></i>
                            {{ $supplier->phone }}
                        </a>
                    @endif
                    @if($supplier->address)
                        <div class="flex items-start gap-4 py-3 px-5 rounded-2xl bg-muted/30 border border-border/50 text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-relaxed">
                            <i data-lucide="map-pin" class="size-5 opacity-40 mt-0.5"></i>
                            <span class="line-clamp-2">{{ $supplier->address }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-8 pt-6 border-t border-border/50 flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-3">
                        <span class="px-4 py-1.5 bg-muted rounded-full text-[8px] font-black uppercase tracking-[0.2em] flex items-center gap-2 border border-border/50">
                            <i data-lucide="receipt" class="size-3.5"></i>
                            {{ $supplier->purchase_orders_count }} Units
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="openEdit('{{ $supplier->id }}')"
                            class="size-11 rounded-[1.25rem] flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors border border-transparent hover:border-border"
                        >
                            <i data-lucide="edit" class="size-5"></i>
                        </button>
                        <button
                            wire:click="delete('{{ $supplier->id }}')"
                            wire:confirm="Permanent decoupling of partner {{ $supplier->name }}. Proceed?"
                            class="size-11 rounded-[1.25rem] flex items-center justify-center text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 transition-colors border border-transparent hover:border-rose-500/20"
                        >
                            <i data-lucide="trash-2" class="size-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="px-2 mt-8">
        {{ $suppliers->links() }}
    </div>

    @if($suppliers->isEmpty())
        <div class="py-32 text-center relative overflow-hidden mx-2 bg-card border border-border rounded-[3.5rem] shadow-glow-sm">
            <i data-lucide="network" class="size-40 text-muted-foreground opacity-[0.03] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></i>
            <div class="relative z-10 space-y-6">
                <div class="size-24 bg-muted/50 rounded-full flex items-center justify-center mx-auto border-4 border-background">
                    <i data-lucide="truck" class="size-10 text-muted-foreground/30 hover:scale-110 transition-transform"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-foreground uppercase tracking-tight">Logistic Gap Detected</h3>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em] mt-3 opacity-40">No confirmed procurement partners detected in directory</p>
                </div>
                <button @click="$wire.openCreate()" class="px-10 py-4 bg-foreground text-background rounded-full text-[10px] font-black uppercase tracking-[0.2em] transform hover:scale-110 active:scale-95 transition-all">
                    Initiate First Partnership
                </button>
            </div>
        </div>
    @endif

    <!-- Add/Edit Partner Modal -->
    <div 
        x-show="showAddDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80 backdrop-blur-xl"
        x-transition:enter="transition duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-cloak
    >
        <div 
            class="bg-card w-full max-w-lg rounded-[4rem] shadow-glow overflow-hidden border border-border relative"
            @click.away="showAddDialog = false"
        >
            <div class="p-12 text-center border-b border-dashed border-border/50 relative">
                <button @click="showAddDialog = false" class="absolute right-10 top-10 size-12 flex items-center justify-center rounded-full bg-muted/30 hover:bg-muted transition-all">
                    <i data-lucide="x" class="size-5 text-slate-500"></i>
                </button>
                <div class="size-20 bg-gradient-to-br from-amber-500/10 to-orange-500/10 rounded-[2rem] border border-amber-500/20 flex items-center justify-center text-amber-500 mx-auto mb-6 shadow-glow shadow-amber-500/5">
                    <i data-lucide="{{ $editingSupplierId ? 'briefcase' : 'user-plus' }}" class="size-10"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground uppercase tracking-tighter">{{ $editingSupplierId ? 'Update Affiliate' : 'Manifest Partner' }}</h2>
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-4 opacity-40">System Procurement Configuration</p>
            </div>

            <div class="p-12 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Legal Identity</label>
                        <input type="text" wire:model="name" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-sm font-black uppercase transition-all outline-none focus:ring-4 focus:ring-amber-500/10">
                        @error('name') <span class="text-[8px] text-rose-500 font-black uppercase tracking-[0.2em] pl-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Primary Liaison</label>
                        <input type="text" wire:model="contact_name" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-sm font-black uppercase transition-all outline-none focus:ring-4 focus:ring-amber-500/10">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Communications</label>
                        <input type="email" wire:model="email" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-xs font-black transition-all outline-none focus:ring-4 focus:ring-amber-500/10">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">VOIP / Registry</label>
                        <input type="text" wire:model="phone" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-sm font-black transition-all outline-none focus:ring-4 focus:ring-amber-500/10">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Operational Localization</label>
                    <textarea wire:model="address" class="h-28 w-full p-6 rounded-[2rem] bg-muted/50 border border-border/50 text-xs font-black outline-none focus:ring-4 focus:ring-amber-500/10 transition-all resize-none uppercase leading-relaxed"></textarea>
                </div>

                <button wire:click="save" class="w-full h-20 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-black text-xs uppercase tracking-[0.3em] rounded-[2rem] shadow-glow shadow-amber-500/30 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-4">
                    <i data-lucide="check-circle" class="size-6"></i>
                    {{ $editingSupplierId ? 'Persist Modification' : 'Index Partner' }}
                </button>
            </div>
            
            <p class="text-center text-[9px] font-black text-muted-foreground uppercase tracking-[0.4em] pb-12 opacity-20 italic">Partner Structural Data Integrity • RMS-STITCH</p>
        </div>
    </div>
</div>
