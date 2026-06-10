<div class="space-y-8" x-data="{ showAddDialog: @entangle('showAddDialog'), showAdjustDialog: @entangle('showAdjustDialog') }">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tight uppercase">Asset Resource Terminal</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Material registry, stock diagnostics, and procurement orchestration</p>
        </div>
        <div class="flex items-center gap-3">
            <button 
                @click="$wire.openCreate()"
                class="h-14 px-8 bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-glow shadow-blue-500/20 rounded-full font-black text-xs uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all flex items-center gap-3 border-0"
            >
                <i data-lucide="package-2" class="size-5"></i>
                Register Material
            </button>
        </div>
    </div>

    <!-- Analytics Matrix -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 px-2">
        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="package" class="size-32"></i>
            </div>
            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mb-3 relative z-10">Asset Catalog</p>
            <div class="text-foreground font-black text-3xl tracking-tighter relative z-10">{{ $stats['total'] }} <span class="text-xs text-muted-foreground uppercase tracking-widest ml-1 opacity-40">Materials</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="alert-triangle" class="size-32 {{ $stats['low'] > 0 ? 'text-rose-500' : 'text-emerald-500' }}"></i>
            </div>
            <p class="text-[10px] font-black {{ $stats['low'] > 0 ? 'text-rose-500' : 'text-emerald-500' }} uppercase tracking-[0.3em] mb-3 relative z-10">Critical Alerts</p>
            <div class="{{ $stats['low'] > 0 ? 'text-rose-500' : 'text-emerald-500' }} font-black text-3xl tracking-tighter relative z-10">
                {{ $stats['low'] }} <span class="text-xs opacity-40 uppercase tracking-widest ml-1">{{ $stats['low'] > 0 ? 'Depleted' : 'Optimal' }}</span>
            </div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="beaker" class="size-32 text-blue-500"></i>
            </div>
            <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.3em] mb-3 relative z-10">Material Units</p>
            <div class="text-blue-500 font-black text-3xl tracking-tighter relative z-10">18 <span class="text-xs opacity-40 uppercase tracking-widest ml-1">Types</span></div>
        </div>

        <div class="p-8 bg-card border border-border rounded-[3rem] shadow-glow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <i data-lucide="warehouse" class="size-32 text-purple-500"></i>
            </div>
            <p class="text-[10px] font-black text-purple-500 uppercase tracking-[0.3em] mb-3 relative z-10">Stock Value</p>
            <div class="text-purple-500 font-black text-3xl tracking-tighter relative z-10">$4.2k <span class="text-xs opacity-40 uppercase tracking-widest ml-1">EST</span></div>
        </div>
    </div>

    <!-- Diagnostics Filter -->
    <div class="p-3 bg-muted/30 backdrop-blur-md border border-border rounded-[2.5rem] shadow-glow-sm mx-2">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40"></i>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search material assets by identification name or SKU..."
                    class="pl-14 h-14 w-full bg-background/50 border border-border/50 rounded-[1.75rem] text-sm font-black uppercase tracking-tight focus:bg-background focus:ring-4 focus:ring-blue-500/10 transition-all outline-none placeholder:text-muted-foreground/30"
                />
            </div>
            <div class="flex items-center gap-3 py-2 px-6 bg-background/50 border border-border/50 rounded-[1.75rem] shrink-0">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="filterLowStock" 
                            class="peer size-6 rounded-lg border-2 border-border/50 text-rose-500 focus:ring-0 transition-all appearance-none checked:bg-rose-500 checked:border-rose-500"
                        >
                        <i data-lucide="check" class="absolute text-white size-4 scale-0 peer-checked:scale-100 transition-transform pointer-events-none left-1"></i>
                    </div>
                    <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] group-hover:text-foreground transition-colors">Critical Thresholds Only</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Material Asset Registry -->
    <div class="mx-2 bg-card border border-border rounded-[3.5rem] overflow-hidden shadow-glow-sm border-t-0 p-1">
        <div class="p-10 border-b border-border bg-muted/[0.15] flex items-center justify-between rounded-t-[3.25rem]">
            <div>
                <h3 class="text-xl font-black text-foreground uppercase tracking-tight">Material Registry</h3>
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-1">Structural Asset Distribution</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-4 py-2 bg-background/50 border border-border/50 rounded-full text-[9px] font-black uppercase tracking-widest text-muted-foreground">
                    <span class="size-2 rounded-full bg-emerald-500"></span> Optimal
                    <span class="size-2 rounded-full bg-rose-500 ml-2"></span> Critical
                </div>
            </div>
        </div>
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="bg-muted/10">
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Material Asset</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Stock Proportion</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Operational Unit</th>
                        <th class="text-left py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Alert Threshold</th>
                        <th class="text-right py-6 px-10 text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Asset Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($ingredients as $ing)
                        @php 
                            $isLow = $ing->stock <= $ing->min_level; 
                            $percent = $ing->min_level > 0 ? min(100, ($ing->stock / ($ing->min_level * 2)) * 100) : 100;
                        @endphp
                        <tr wire:key="ing-{{ $ing->id }}" class="group hover:bg-muted/30 transition-all">
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-5">
                                    <div class="size-14 rounded-[1.5rem] bg-gradient-to-br from-blue-500/20 to-indigo-500/20 border border-blue-500/30 flex items-center justify-center text-blue-500 font-black text-xl shadow-glow shadow-blue-500/5 group-hover:scale-110 transition-transform">
                                        @php
                                            $icon = match(strtolower($ing->unit)) {
                                                'kg', 'g' => 'scale',
                                                'l', 'ml' => 'droplet',
                                                'pcs', 'box' => 'package-2',
                                                default => 'layers'
                                            };
                                        @endphp
                                        <i data-lucide="{{ $icon }}" class="size-6"></i>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-black text-foreground text-sm uppercase tracking-tight">{{ $ing->name }}</p>
                                        <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest opacity-40">Asset ID: {{ str_pad($ing->id, 6, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-10">
                                <div class="space-y-2 max-w-[120px]">
                                    <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-tight">
                                        <span class="{{ $isLow ? 'text-rose-500 pulse' : 'text-foreground' }}">{{ (float)$ing->stock }} {{ $ing->unit }}</span>
                                        <span class="opacity-30">{{ round($percent) }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-muted/50 rounded-full overflow-hidden border border-border/50 p-[1px]">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 {{ $isLow ? 'bg-gradient-to-r from-rose-500 to-orange-500' : 'bg-gradient-to-r from-emerald-500 to-blue-500' }}"
                                            style="width: {{ $percent }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-10">
                                <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] bg-muted/50 border border-border/50 text-muted-foreground">
                                    {{ $ing->unit }}
                                </span>
                            </td>
                            <td class="py-6 px-10">
                                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] flex items-center gap-2">
                                    <i data-lucide="bell" class="size-3.5"></i>
                                    {{ (float)$ing->min_level }} <span class="opacity-40 text-[8px]">{{ $ing->unit }}</span>
                                </span>
                            </td>
                            <td class="py-6 px-10 text-right">
                                <div class="flex justify-end gap-3">
                                    <button 
                                        wire:click="openAdjust('{{ $ing->id }}')"
                                        class="h-11 px-5 rounded-[1.25rem] border border-border hover:bg-muted text-[9px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-2 group/btn"
                                    >
                                        <i data-lucide="refresh-cw" class="size-4 group-hover/btn:rotate-180 transition-transform"></i>
                                        Calibrate
                                    </button>
                                    <button
                                        wire:click="openEdit('{{ $ing->id }}')"
                                        class="size-11 rounded-[1.25rem] flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors border border-transparent hover:border-border"
                                    >
                                        <i data-lucide="edit" class="size-5"></i>
                                    </button>
                                    <button
                                        wire:click="delete('{{ $ing->id }}')"
                                        wire:confirm="Permanent decommissioning of material asset {{ $ing->name }}. Proceed?"
                                        class="size-11 rounded-[1.25rem] flex items-center justify-center text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 transition-colors border border-transparent hover:border-rose-500/20"
                                    >
                                        <i data-lucide="trash-2" class="size-5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-32 text-center relative overflow-hidden">
                                <i data-lucide="package-2" class="size-40 text-muted-foreground opacity-[0.03] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></i>
                                <div class="relative z-10">
                                    <h3 class="text-2xl font-black text-foreground uppercase tracking-tight">Registry Depleted</h3>
                                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em] mt-3 opacity-40">No localized material assets detected</p>
                                    <button @click="$wire.openCreate()" class="mt-8 px-8 py-3 bg-foreground text-background rounded-full text-[10px] font-black uppercase tracking-widest hover:scale-110 active:scale-95 transition-all">
                                        Establish First Asset
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-10 py-8 border-t border-border bg-muted/5">
            {{ $ingredients->links() }}
        </div>
    </div>

    <!-- Calibrate Stock Modal (Adjust) -->
    <div 
        x-show="showAdjustDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80 backdrop-blur-xl"
        x-transition:enter="transition duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-cloak
    >
        <div 
            class="bg-card w-full max-w-lg rounded-[4rem] shadow-glow overflow-hidden border border-border relative"
            @click.away="showAdjustDialog = false"
        >
            <div class="p-12 text-center border-b border-dashed border-border/50">
                <button @click="showAdjustDialog = false" class="absolute right-10 top-10 size-12 flex items-center justify-center rounded-full bg-muted/30 hover:bg-muted transition-all">
                    <i data-lucide="x" class="size-5 text-slate-500"></i>
                </button>
                <div class="size-20 bg-blue-500/10 rounded-[2rem] border border-blue-500/20 flex items-center justify-center text-blue-500 mx-auto mb-6">
                    <i data-lucide="settings-2" class="size-10"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground uppercase tracking-tighter">Calibrate Asset</h2>
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-4 opacity-40">
                    {{ $selectedIngredient?->name }} • Current: {{ (float)$selectedIngredient?->stock }} {{ $selectedIngredient?->unit }}
                </p>
            </div>

            <div class="p-12 space-y-8">
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Modification Vector</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['restock' => 'Addition', 'wastage' => 'Depletion', 'correction' => 'Sync'] as $key => $label)
                            <button 
                                type="button" 
                                wire:click="$set('adjustType', '{{ $key }}')"
                                class="h-16 rounded-[1.5rem] border text-[9px] font-black uppercase tracking-[0.2em] transition-all flex flex-col items-center justify-center gap-1
                                {{ $adjustType === $key ? 'bg-blue-600 text-white border-blue-600 shadow-glow shadow-blue-500/20' : 'bg-muted/30 border-border/50 text-muted-foreground hover:bg-muted' }}"
                            >
                                <i data-lucide="{{ $key === 'restock' ? 'plus-circle' : ($key === 'wastage' ? 'minus-circle' : 'refresh-cw') }}" class="size-5 mb-1"></i>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Quantity Manifest ({{ $selectedIngredient?->unit }})</label>
                    <div class="relative">
                        <i data-lucide="calculator" class="absolute left-6 top-1/2 -translate-y-1/2 size-6 opacity-20"></i>
                        <input type="number" wire:model="adjustAmount" class="pl-16 h-20 w-full rounded-[2rem] bg-muted/50 border border-border/50 text-2xl font-black outline-none focus:bg-background focus:ring-4 focus:ring-blue-500/10 transition-all">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Transaction Log Entry</label>
                    <input type="text" wire:model="adjustReason" placeholder="Stock delivery, damage info, etc." class="h-14 w-full px-7 rounded-[1.5rem] bg-muted/50 border border-border/50 text-xs font-black uppercase transition-all outline-none focus:bg-background">
                </div>

                <button wire:click="adjustStock" class="w-full h-20 bg-foreground text-background font-black text-xs uppercase tracking-[0.3em] rounded-[2rem] shadow-glow hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-3">
                    <i data-lucide="save" class="size-5"></i>
                    Propagate Adjustment
                </button>
            </div>
            
            <p class="text-center text-[9px] font-black text-muted-foreground uppercase tracking-[0.4em] pb-12 opacity-20 italic">Validated Inventory Sync • RMS-STITCH SECURE</p>
        </div>
    </div>

    <!-- Configure Asset Modal (Add/Edit) -->
    <div 
        x-show="showAddDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80 backdrop-blur-xl"
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
                <div class="size-20 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-[2rem] border border-blue-500/20 flex items-center justify-center text-blue-500 mx-auto mb-6 shadow-glow shadow-blue-500/5">
                    <i data-lucide="{{ $editingIngredientId ? 'package' : 'shopping-cart' }}" class="size-10"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground uppercase tracking-tighter">{{ $editingIngredientId ? 'Reconfigure Asset' : 'Manifest Asset' }}</h2>
                <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em] mt-4 opacity-40">System Material Configuration</p>
            </div>

            <div class="p-12 space-y-8">
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Asset Nomenclature</label>
                    <div class="relative">
                        <i data-lucide="edit-3" class="absolute left-6 top-1/2 -translate-y-1/2 size-6 opacity-20"></i>
                        <input type="text" wire:model="name" class="pl-16 h-16 w-full rounded-[1.75rem] bg-muted/50 border border-border/50 text-sm font-black uppercase transition-all outline-none focus:ring-4 focus:ring-blue-500/10">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Initial Base Stock</label>
                        <input type="number" step="0.001" wire:model="stock" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-sm font-black transition-all outline-none focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-[0.3em] pl-1">Measurement Unit</label>
                        <select wire:model="unit" class="h-16 w-full px-6 rounded-[1.75rem] bg-muted/50 border border-border/50 text-[10px] font-black uppercase tracking-[0.2em] transition-all outline-none focus:ring-4 focus:ring-blue-500/10 appearance-none">
                            <option value="kg">KILOGRAMS (kg)</option>
                            <option value="g">GRAMS (g)</option>
                            <option value="l">LITERS (l)</option>
                            <option value="ml">MILLILITERS (ml)</option>
                            <option value="pcs">PIECES (pcs)</option>
                            <option value="box">CONTAINER (box)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-3 p-8 bg-rose-500/[0.03] rounded-[2.5rem] border border-dashed border-rose-500/20">
                    <label class="text-[10px] font-black text-rose-500 uppercase tracking-[0.3em] pl-1">Critical Threshold (Alert)</label>
                    <div class="relative">
                        <i data-lucide="alert-triangle" class="absolute left-6 top-1/2 -translate-y-1/2 size-6 text-rose-500 opacity-40"></i>
                        <input type="number" step="0.001" wire:model="min_level" class="pl-16 h-16 w-full rounded-[1.5rem] bg-background border border-rose-500/30 text-rose-500 text-sm font-black outline-none focus:ring-4 focus:ring-rose-500/10 transition-all">
                    </div>
                    <p class="text-[8px] font-black text-rose-500/50 uppercase tracking-[0.2em] mt-3 italic">System will trigger critical status when stock penetrates this level</p>
                </div>

                <button wire:click="save" class="w-full h-20 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-black text-xs uppercase tracking-[0.3em] rounded-[2rem] shadow-glow shadow-blue-500/30 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-4">
                    <i data-lucide="check-circle" class="size-6"></i>
                    {{ $editingIngredientId ? 'Persist Modification' : 'Index Into Registry' }}
                </button>
            </div>
            
            <p class="text-center text-[9px] font-black text-muted-foreground uppercase tracking-[0.4em] pb-12 opacity-20 italic">Asset Structural Data Integrity • RMS-STITCH</p>
        </div>
    </div>
</div>
