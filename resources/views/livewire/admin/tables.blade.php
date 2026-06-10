<div class="space-y-8" x-data="{ 
    showAddDialog: @entangle('showAddDialog'), 
    showBulkDialog: @entangle('showBulkDialog'),
    showReservationDialog: @entangle('showReservationDialog'),
    showGroupConfirm: @entangle('showGroupConfirm')
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Table Management</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Coordinate floor occupancy and guest allocations</p>
        </div>

        <div class="flex items-center gap-4">
            <button 
                @click="showBulkDialog = true"
                class="h-12 px-6 border-2 border-primary/20 text-primary hover:bg-primary hover:text-white transition-all duration-300 rounded-2xl font-bold flex items-center gap-2 group shadow-sm"
            >
                <i data-lucide="layout-grid" class="size-5"></i>
                Bulk Add
            </button>

            <button
                @click="$wire.toggleGroupMode()"
                class="h-12 px-6 border-2 {{ $isGroupMode ? 'border-rose-500 bg-rose-500 text-white shadow-rose-500/20' : 'border-indigo-500/20 text-indigo-600 hover:bg-indigo-500 hover:text-white' }} transition-all duration-300 rounded-2xl font-bold flex items-center gap-2 shadow-sm"
            >
                <i data-lucide="{{ $isGroupMode ? 'x-circle' : 'merge' }}" class="size-5"></i>
                {{ $isGroupMode ? 'Cancel Grouping' : 'Group Tables' }}
            </button>

            @if($isGroupMode && count($selectedForGroup) >= 2)
                <button
                    @click="showGroupConfirm = true"
                    class="h-12 px-8 bg-indigo-600 text-white shadow-xl shadow-indigo-600/25 hover:scale-[1.03] active:scale-95 transition-all duration-300 rounded-2xl font-bold flex items-center gap-2"
                >
                    <i data-lucide="link" class="size-5"></i>
                    Finalize Group ({{ count($selectedForGroup) }})
                </button>
            @endif
        </div>
    </div>

    <!-- Status Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 bg-card border border-border/50 rounded-3xl shadow-sm relative overflow-hidden group">
            <div class="absolute -right-2 -top-2 text-foreground/5 dark:text-white/5 group-hover:scale-110 transition-transform duration-500">
                <i data-lucide="table-2" class="size-24"></i>
            </div>
            <p class="text-[10px] font-black text-muted-foreground/60 uppercase tracking-widest mb-1 relative z-10">Total Tables</p>
            <div class="text-foreground font-black text-4xl tracking-tighter relative z-10">{{ $this->stats['total'] }}</div>
        </div>

        <div class="p-6 bg-card border border-border/50 rounded-3xl shadow-sm relative overflow-hidden group">
            <div class="absolute -right-2 -top-2 text-emerald-500/5 group-hover:scale-110 transition-transform duration-500">
                <i data-lucide="check-circle-2" class="size-24"></i>
            </div>
            <p class="text-[10px] font-black text-emerald-500/60 uppercase tracking-widest mb-1 relative z-10">Free</p>
            <div class="text-emerald-500 font-black text-4xl tracking-tighter relative z-10">{{ $this->stats['free'] }}</div>
        </div>

        <div class="p-6 bg-card border border-border/50 rounded-3xl shadow-sm relative overflow-hidden group">
            <div class="absolute -right-2 -top-2 text-rose-500/5 group-hover:scale-110 transition-transform duration-500">
                <i data-lucide="user-minus" class="size-24"></i>
            </div>
            <p class="text-[10px] font-black text-rose-500/60 uppercase tracking-widest mb-1 relative z-10">Occupied</p>
            <div class="text-rose-500 font-black text-4xl tracking-tighter relative z-10">{{ $this->stats['occupied'] }}</div>
        </div>

        <div class="p-6 bg-card border border-border/50 rounded-3xl shadow-sm relative overflow-hidden group">
            <div class="absolute -right-2 -top-2 text-orange-500/5 group-hover:scale-110 transition-transform duration-500">
                <i data-lucide="calendar-days" class="size-24"></i>
            </div>
            <p class="text-[10px] font-black text-orange-500/60 uppercase tracking-widest mb-1 relative z-10">Reserved</p>
            <div class="text-orange-500 font-black text-4xl tracking-tighter relative z-10">{{ $this->stats['reserved'] }}</div>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6 pb-12">
        @forelse($this->tables as $table)
            <div
                wire:key="table-{{ $table->id }}"
                @if($isGroupMode && $table->status->value === 'free' && (!$table->group_id || in_array($table->number, $selectedForGroup))) 
                    @click="$wire.toggleGroupSelection({{ $table->number }})" 
                @endif
                class="group relative p-6 bg-card border hover:border-primary/30 transition-all duration-500 rounded-[2.5rem] hover:shadow-2xl hover:shadow-primary/5 hover:-translate-y-1 
                {{ $isGroupMode && $table->status->value === 'free' && !$table->group_id ? 'cursor-pointer hover:border-indigo-500 active:scale-95' : 'border-border/50' }} 
                {{ in_array($table->number, $selectedForGroup) ? 'ring-4 ring-indigo-500/20 border-indigo-500 bg-indigo-500/5' : '' }}"
            >
                <!-- Group Indicators -->
                @if($table->group_id)
                    <div class="absolute top-6 right-6 flex items-center gap-1.5 px-3 py-1 bg-indigo-500/10 text-indigo-600 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-indigo-500/20">
                        <i data-lucide="link" class="size-3"></i>
                        GRP
                    </div>
                @endif

                <div class="flex items-start justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="size-16 rounded-[1.75rem] bg-background border border-border/50 flex items-center justify-center shadow-inner group-hover:border-primary/30 transition-colors">
                            <span class="text-3xl font-black text-foreground">{{ $table->number }}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-bold text-lg leading-none">Table {{ $table->number }}</h3>
                                @if($table->is_primary) 
                                    <i data-lucide="crown" class="size-4 text-amber-500 fill-amber-500"></i>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground/60 font-bold uppercase tracking-widest leading-none">
                                <i data-lucide="users" class="size-3"></i>
                                {{ $table->capacity }} guests
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">
                        <button
                            wire:click.stop="openEdit('{{ $table->id }}')"
                            class="p-2.5 bg-background shadow-sm rounded-2xl text-muted-foreground hover:text-primary border border-border/50 hover:bg-primary/5 transition-all"
                            title="Edit"
                        >
                            <i data-lucide="settings" class="size-4"></i>
                        </button>
                        @if($table->group_id)
                            <button
                                wire:click.stop="ungroup('{{ $table->group_id }}')"
                                class="p-2.5 bg-background shadow-sm rounded-2xl text-orange-500 hover:bg-orange-500/10 border border-border/50 transition-all"
                                title="Ungroup"
                            >
                                <i data-lucide="link-2-off" class="size-4"></i>
                            </button>
                        @endif
                        <button
                            wire:click.stop="delete('{{ $table->id }}')"
                            wire:confirm="Permanent deletion of Table {{ $table->number }}. Proceed?"
                            class="p-2.5 bg-background shadow-sm rounded-2xl text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 border border-border/50 transition-all"
                            title="Delete"
                        >
                            <i data-lucide="trash-2" class="size-4"></i>
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Status Selector -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="w-full h-14 px-5 rounded-2xl bg-muted/30 border border-border/50 flex items-center justify-between text-sm font-bold transition-all hover:bg-muted/50 outline-none focus:ring-4 focus:ring-primary/10"
                        >
                            <span>{{ [
                                'free' => 'Available',
                                'occupied' => 'Occupied',
                                'reserved' => 'Reserved'
                            ][$table->status->value] ?? strtoupper($table->status->value) }}</span>
                            <i data-lucide="chevron-down" class="size-4 text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute top-full mt-2 left-0 w-full bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[50] backdrop-blur-xl"
                            x-cloak
                        >
                            @foreach(['free' => 'Available', 'occupied' => 'Occupied', 'reserved' => 'Reserved'] as $val => $label)
                                <button 
                                    @click="$wire.updateStatus('{{ $table->id }}', '{{ $val }}'); open = false"
                                    class="w-full px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $table->status->value === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @php
                        $statusClass = match($table->status->value) {
                            'free' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                            'occupied' => 'bg-rose-500/10 text-rose-600 border-rose-500/20',
                            'reserved' => 'bg-orange-500/10 text-orange-600 border-orange-500/20',
                            default => 'bg-muted text-muted-foreground'
                        };
                        $statusText = match($table->status->value) {
                            'free' => 'FREE',
                            'occupied' => 'OCCUPIED',
                            'reserved' => 'RESERVED',
                            default => strtoupper($table->status->value)
                        };
                    @endphp
                    <div class="w-full h-10 flex items-center justify-center rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border {{ $statusClass }} transition-colors">
                        <span class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-current {{ $table->status->value === 'occupied' ? 'animate-pulse' : '' }}"></span>
                            {{ $statusText }}
                        </span>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-6">
                    @if($table->status->value === 'reserved')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between px-1">
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-muted-foreground/60 uppercase tracking-widest leading-none">Guest</p>
                                    <h4 class="text-sm font-black truncate mt-1">{{ $table->reserved_by ?? 'Anonymous' }}</h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click.stop="checkIn('{{ $table->number }}')"
                                        class="h-10 px-4 bg-emerald-500 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-500/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
                                    >
                                        Check In
                                    </button>
                                </div>
                            </div>
                            @if($table->reserved_time)
                                <div class="flex items-center gap-2 text-[9px] font-black text-orange-600 uppercase tracking-widest bg-orange-500/5 w-fit px-3 py-1.5 rounded-lg border border-orange-500/10">
                                    <i data-lucide="clock" class="size-3"></i>
                                    {{ \Carbon\Carbon::parse($table->reserved_time)->format('h:i A') }}
                                </div>
                            @endif
                        </div>
                    @else
                        <button
                            @if($table->status->value === 'free') wire:click.stop="openReservation('{{ $table->id }}')" @endif
                            class="w-full h-14 rounded-2xl border-2 border-dashed border-primary/20 text-primary hover:bg-primary/5 text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-3 group/btn"
                        >
                            <i data-lucide="calendar-plus" class="size-4 group-hover/btn:scale-110 transition-transform"></i>
                            Reserve
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-40 flex flex-col items-center justify-center bg-card/40 backdrop-blur-md border border-dashed border-border/50 rounded-[3rem]">
                <div class="size-24 bg-primary/10 rounded-full flex items-center justify-center mb-8 border border-primary/20 text-primary">
                    <i data-lucide="layout-grid" class="size-12"></i>
                </div>
                <h3 class="text-3xl font-black text-foreground mb-4">No Floor Plan Detected</h3>
                <p class="text-muted-foreground font-medium max-w-sm text-center mb-10">
                    It looks like you haven't established any tables yet. Start by adding your first seating unit to begin orchestrating orders.
                </p>
                <button 
                    @click="$wire.openCreate()"
                    class="h-16 px-10 bg-primary text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3"
                >
                    <i data-lucide="plus" class="size-5"></i>
                    Initialize First Table
                </button>
            </div>
        @endforelse
    </div>

    <!-- Modals Registry -->
    
    <!-- Edit/Add Modal -->
    <div 
        x-show="showAddDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80"
        x-cloak
    >
        <div 
            class="bg-card w-full max-w-lg rounded-[2.5rem] shadow-2xl border border-border/60 relative overflow-hidden"
            @click.away="showAddDialog = false"
        >
            <div class="p-8 border-b border-border/40 bg-muted/20 relative">
                <button @click="showAddDialog = false" class="absolute right-8 top-8 p-3 bg-background hover:bg-muted text-muted-foreground rounded-2xl transition-all border border-border/50">
                    <i data-lucide="x" class="size-5"></i>
                </button>
                <div class="size-16 bg-primary/10 rounded-2xl border border-primary/20 flex items-center justify-center text-primary mb-6 shadow-glow shadow-primary/5">
                    <i data-lucide="table-2" class="size-8"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground tracking-tighter">{{ $editingTableId ? 'Edit Table Settings' : 'Add New Table' }}</h2>
                <p class="text-sm text-muted-foreground font-medium mt-1">Configure physical seating units</p>
            </div>

            <div class="p-8 space-y-8">
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Table Number</label>
                        <input type="number" wire:model="number" class="w-full h-14 px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background text-base font-bold transition-all outline-none focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Guest Capacity</label>
                        <input type="number" wire:model="capacity" class="w-full h-14 px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background text-base font-bold transition-all outline-none focus:ring-4 focus:ring-primary/10">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Initial Status</label>
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            type="button"
                            @click="open = !open"
                            class="w-full h-14 px-5 rounded-2xl bg-muted/30 border border-border/50 flex items-center justify-between text-sm font-bold uppercase tracking-widest transition-all outline-none focus:ring-4 focus:ring-primary/10 hover:bg-muted/40"
                        >
                            <span>{{ strtoupper($status ?: 'Select Status') }}</span>
                            <i data-lucide="chevron-down" class="size-4 text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute top-full mt-2 left-0 w-full bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[120] backdrop-blur-xl"
                            x-cloak
                        >
                            @foreach(\App\Enums\TableStatus::cases() as $case)
                                <button 
                                    type="button"
                                    @click="$wire.set('status', '{{ $case->value }}'); open = false"
                                    class="w-full px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $status === $case->value ? 'text-primary bg-primary/5' : 'text-foreground' }}"
                                >
                                    {{ strtoupper($case->value) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button @click="showAddDialog = false" class="flex-1 h-16 rounded-2xl border-2 border-border/50 font-black text-base hover:bg-muted transition-all">Cancel</button>
                    <button wire:click="save" class="flex-[2] h-16 bg-primary text-white font-extrabold text-base rounded-2xl shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span>{{ $editingTableId ? 'Save Configuration' : 'Establish Table' }}</span>
                        <i data-lucide="check" class="size-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Add Modal -->
    <div 
        x-show="showBulkDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80"
        x-cloak
    >
        <div class="bg-card w-full max-w-lg rounded-[2.5rem] shadow-2xl border border-border/60 p-10 space-y-10">
            <div class="text-center">
                <div class="size-20 bg-indigo-500/10 rounded-3xl border border-indigo-500/20 flex items-center justify-center text-indigo-500 mx-auto mb-6">
                    <i data-lucide="layout-grid" class="size-10"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground tracking-tighter">Bulk Initialization</h2>
                <p class="text-sm text-muted-foreground font-medium mt-1">Deploy multiple nodes simultaneously</p>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Starting #</label>
                    <input type="number" wire:model="startNumber" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 text-base font-bold transition-all outline-none">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">How Many?</label>
                    <input type="number" wire:model="quantity" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 text-base font-bold transition-all outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Capacity for all</label>
                <input type="number" wire:model="bulkCapacity" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 text-base font-bold transition-all outline-none">
            </div>

            <div class="flex gap-4">
                <button @click="showBulkDialog = false" class="flex-1 h-16 rounded-2xl border-2 border-border/50 font-black text-base hover:bg-muted transition-all">Cancel</button>
                <button wire:click="createBulk" class="flex-[2] h-16 bg-indigo-600 text-white font-extrabold text-base rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span>Deploy {{ $quantity }} Tables</span>
                    <i data-lucide="zap" class="size-5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Allocation Modal (Reservation) -->
    <div 
        x-show="showReservationDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80"
        x-cloak
    >
        <div class="bg-card w-full max-w-lg rounded-[2.5rem] shadow-2xl border border-border/60 relative overflow-hidden">
            <div class="p-8 text-center border-b border-border/40 bg-muted/20">
                <div class="size-16 bg-orange-500/10 rounded-2xl border border-orange-500/20 flex items-center justify-center text-orange-500 mx-auto mb-4">
                    <i data-lucide="calendar-plus" class="size-8"></i>
                </div>
                <h2 class="text-3xl font-black text-foreground tracking-tighter">New Allocation</h2>
                <p class="text-sm text-muted-foreground font-medium mt-1">Table {{ $selectedTableForRes?->number }} • Reservation Registry</p>
            </div>
            
            <div class="p-10 space-y-6">
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Guest Identity</label>
                    <input type="text" wire:model="customerName" placeholder="Full Name" class="h-14 w-full px-6 rounded-2xl bg-muted/30 border border-border/50 text-base font-bold transition-all outline-none">
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Phone Reference</label>
                    <input type="text" wire:model="customerPhone" placeholder="+1 (555) 000-0000" class="h-14 w-full px-6 rounded-2xl bg-muted/30 border border-border/50 text-base font-bold transition-all outline-none">
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Allocation Timestamp</label>
                        <input type="date" wire:model="resDate" class="h-14 w-full px-6 rounded-2xl bg-muted/30 border border-border/50 text-sm font-bold transition-all outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">Start Time</label>
                        <input type="time" wire:model="startTime" class="h-14 w-full px-6 rounded-2xl bg-muted/30 border border-border/50 text-sm font-bold transition-all outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest pl-1">End Time</label>
                        <input type="time" wire:model="endTime" class="h-14 w-full px-6 rounded-2xl bg-muted/30 border border-border/50 text-sm font-bold transition-all outline-none">
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button @click="showReservationDialog = false" class="flex-1 h-16 rounded-2xl border-2 border-border/50 font-black text-base hover:bg-muted transition-all">Back</button>
                    <button wire:click="createReservation" class="flex-[2] h-16 bg-orange-600 text-white font-extrabold text-base rounded-2xl shadow-xl shadow-orange-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span>Confirm Booking</span>
                        <i data-lucide="check" class="size-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Confirmation Modal -->
    <div 
        x-show="showGroupConfirm" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/80"
        x-cloak
    >
        <div class="bg-card w-full max-w-md rounded-[2.5rem] shadow-2xl border border-border/60 p-10 space-y-8">
            <div class="text-center">
                <div class="size-16 bg-indigo-500/10 rounded-2xl border border-indigo-500/20 flex items-center justify-center text-indigo-500 mx-auto mb-6">
                    <i data-lucide="merge" class="size-8"></i>
                </div>
                <h2 class="text-2xl font-black text-foreground tracking-tighter">Sync Configuration</h2>
                <p class="text-sm text-muted-foreground font-medium mt-1">Select the primary (master) table</p>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                @foreach(collect($selectedForGroup)->sort() as $num)
                    <button 
                        wire:click="$set('primaryTableForGroup', {{ $num }})"
                        class="h-16 rounded-2xl border-2 {{ $primaryTableForGroup == $num ? 'bg-indigo-600 text-white border-indigo-700 shadow-lg shadow-indigo-500/20' : 'bg-muted/30 border-border/50 text-foreground hover:bg-muted' }} transition-all font-black text-xl flex items-center justify-center"
                    >
                        {{ $num }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-4">
                <button @click="showGroupConfirm = false" class="flex-1 h-16 rounded-2xl border-2 border-border/50 font-black text-base hover:bg-muted transition-all">Cancel</button>
                <button 
                    wire:click="createGroup"
                    @disabled(!$primaryTableForGroup)
                    class="flex-[2] h-16 bg-indigo-600 text-white font-extrabold text-base rounded-2xl shadow-xl shadow-indigo-600/20 disabled:opacity-50 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2"
                >
                    <i data-lucide="link" class="size-5"></i>
                    Finalize Group
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('notify', (data) => {
            const payload = data[0] || data;
            if (window.showToast) {
                window.showToast(payload.message, payload.type);
            }
        });
    </script>
    @endscript
</div>
