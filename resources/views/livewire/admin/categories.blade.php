<div class="space-y-8" x-data="{ 
    showDialog: @entangle('showDialog'), 
    showBulkDialog: @entangle('showBulkDialog'),
    bulkCount: 0,
    updateBulkCount() {
        const input = $wire.bulkInput || '';
        this.bulkCount = input.split(/[\n,]+/).map(s => s.trim()).filter(Boolean).length;
    }
}" x-init="updateBulkCount(); $watch('$wire.bulkInput', () => updateBulkCount())">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-foreground to-foreground/70">
                Menu Categories
            </h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Organize and curate your restaurant's digital menu</p>
        </div>

        <div class="flex items-center gap-4">
            <button 
                @click="showBulkDialog = true"
                class="h-12 px-6 border-2 border-primary/30 text-primary hover:bg-primary hover:text-white transition-all duration-300 rounded-2xl font-bold flex items-center gap-2 group shadow-sm hover:shadow-primary/20"
            >
                <i data-lucide="layers" class="size-5 group-hover:rotate-12 transition-transform"></i>
                Bulk Add
            </button>

            <button 
                @click="$wire.openCreate()"
                class="h-12 px-8 bg-gradient-to-br from-primary to-primary/80 hover:from-primary/90 hover:to-primary text-white shadow-xl shadow-primary/25 transition-all duration-300 hover:scale-[1.03] active:scale-95 rounded-2xl border-0 font-bold flex items-center gap-2"
            >
                <i data-lucide="plus-circle" class="size-5"></i>
                Add Category
            </button>
        </div>
    </div>

    <!-- Search Bar & Filters -->
    <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center bg-card/40 backdrop-blur-xl p-4 rounded-3xl border border-border/50 shadow-sm">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i data-lucide="search" class="h-5 w-5 text-muted-foreground/60"></i>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or description..."
                class="pl-12 h-14 bg-background/50 border-border/50 border focus:border-orange-500/50 rounded-2xl shadow-inner text-base w-full focus:outline-none focus:ring-4 focus:ring-orange-500/10 transition-all placeholder:text-muted-foreground/40"
            />
        </div>

            <div class="relative flex items-center gap-2 bg-background/50 px-4 py-2 rounded-2xl border border-border/50" x-data="{ open: false }">
                <i data-lucide="list-filter" class="w-4 h-4 text-orange-500"></i>
                <span class="text-sm font-bold text-muted-foreground">Status</span>
                <div class="h-4 w-px bg-border/50 mx-1"></div>
                <button 
                    @click="open = !open"
                    class="flex items-center gap-2 text-sm font-bold text-foreground focus:outline-none min-w-[120px] justify-between"
                >
                    <span>
                        {{ [
                            'all' => 'All Items',
                            'active' => 'Active Only',
                            'inactive' => 'Inactive'
                        ][$filterStatus] ?? 'Status' }}
                    </span>
                    <i data-lucide="chevron-down" class="size-4 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    class="absolute top-full mt-2 left-0 w-full min-w-[160px] bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[50] backdrop-blur-xl"
                    x-cloak
                >
                    @foreach(['all' => 'All Items', 'active' => 'Active Only', 'inactive' => 'Inactive'] as $val => $label)
                        <button 
                            @click="$wire.set('filterStatus', '{{ $val }}'); open = false"
                            class="w-full px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $filterStatus === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6">
        @forelse($categories as $category)
            <div class="group relative bg-card/60 backdrop-blur-md border border-border/50 hover:border-orange-500/30 transition-all duration-500 rounded-[2rem] overflow-hidden hover:shadow-2xl hover:shadow-orange-500/5 hover:-translate-y-1">
                <!-- Status Badge (Top Left) -->
                <div class="absolute top-4 left-4 z-10">
                    <span class="flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full {{ $category->is_active ? 'bg-emerald-400' : 'bg-rose-400' }} opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 {{ $category->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    </span>
                </div>

                <!-- Actions (Top Right) -->
                <div class="absolute top-3 right-3 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0 z-10">
                    <button
                        wire:click="openEdit('{{ $category->id }}')"
                        class="p-2.5 bg-card/90 backdrop-blur-md hover:bg-primary/10 text-muted-foreground hover:text-primary rounded-2xl shadow-lg border border-border/50 transition-all active:scale-90"
                        title="Edit"
                    >
                        <i data-lucide="pencil" class="w-4 h-4 text-primary"></i>
                    </button>
                    <button
                        wire:click="delete('{{ $category->id }}')"
                        wire:confirm="Confirm deletion of '{{ $category->name }}'?"
                        class="p-2.5 bg-card/90 backdrop-blur-md hover:bg-destructive/10 text-muted-foreground hover:text-destructive rounded-2xl shadow-lg border border-border/50 transition-all active:scale-90"
                        title="Delete"
                    >
                        <i data-lucide="trash-2" class="size-4 text-destructive"></i>
                    </button>
                </div>

                <div class="p-6 flex flex-col items-center text-center space-y-5">
                    <!-- Icon/Gradient -->
                    <div class="relative">
                        <div class="w-20 h-20 rounded-3xl flex items-center justify-center text-3xl font-black text-white shadow-2xl bg-gradient-to-br {{ $this->getGradients($category->name) }} transform transition-transform group-hover:rotate-6 duration-500">
                            {{ strtoupper(substr($category->name, 0, 1)) }}
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-card rounded-xl px-2 py-1 border border-border shadow-sm">
                            <span class="text-[10px] font-black text-foreground">{{ $category->menu_items_count }}</span>
                        </div>
                    </div>

                    <div class="space-y-2 w-full">
                        <h3 class="font-bold text-lg text-foreground truncate px-2 group-hover:text-orange-600 transition-colors" title="{{ $category->name }}">
                            {{ $category->name }}
                        </h3>
                        <p class="text-xs text-muted-foreground font-medium uppercase tracking-widest opacity-60">
                            {{ $category->is_active ? 'Publicly Visible' : 'Private / Hidden' }}
                        </p>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="h-1 w-full bg-gradient-to-r {{ $this->getGradients($category->name) }} opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-32 bg-card/20 backdrop-blur-sm rounded-[3rem] border-2 border-dashed border-border/50 transition-all">
                <div class="relative mb-8">
                    <div class="w-24 h-24 bg-orange-500/10 text-orange-600 rounded-full flex items-center justify-center animate-pulse">
                        <i data-lucide="shapes" class="w-12 h-12"></i>
                    </div>
                    <div class="absolute -top-2 -right-2 bg-background border border-border p-2 rounded-xl shadow-sm rotate-12">
                        <i data-lucide="search" class="w-4 h-4 text-muted-foreground"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-foreground mb-3">No matching categories</h3>
                <p class="text-muted-foreground max-w-sm text-center mb-10 font-medium">
                    {{ $search ? "We couldn't find any categories matching '$search'." : "Start your menu journey by creating your first category today!" }}
                </p>
                <div class="flex gap-4">
                    @if($search || $filterStatus !== 'all')
                        <button 
                            wire:click="clearFilters"
                            class="h-14 px-10 rounded-2xl border-2 border-primary/20 text-primary font-black transition-all hover:bg-primary/10 active:scale-95 flex items-center gap-2"
                        >
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                            Clear Filters
                        </button>
                    @endif
                    <button 
                        @click="$wire.openCreate()"
                        class="bg-primary hover:bg-primary/90 text-white h-14 px-10 rounded-2xl font-black shadow-lg shadow-primary/30 transition-all hover:scale-105 active:scale-95 flex items-center gap-2"
                    >
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        Begin Setup
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-12 flex justify-center">
        {{ $categories->links() }}
    </div>


    <!-- Add/Edit Modal -->
    <div 
        x-show="showDialog" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        x-cloak
    >
        <div 
            x-show="showDialog"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-background/80"
            @click="showDialog = false"
        ></div>

        <div 
            x-show="showDialog"
            x-transition:enter="transition ease-out duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
            x-transition:enter-start="opacity-0 scale-90 translate-y-10"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-card w-full max-w-lg rounded-[2.5rem] shadow-2xl relative overflow-hidden border border-border/60"
        >
            <div class="p-8 border-b border-border/40 bg-muted/20 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-foreground">{{ $editingCategoryId ? 'Edit Category' : 'New Category' }}</h2>
                        <p class="text-sm text-muted-foreground mt-1 font-medium">{{ $editingCategoryId ? 'Refine category details and visibility' : 'Create a fresh section for your menu items' }}</p>
                    </div>
                    <button @click="showDialog = false" class="p-3 bg-background hover:bg-muted text-muted-foreground rounded-2xl transition-all border border-border/50">
                        <i data-lucide="x" class="size-5"></i>
                    </button>
                </div>
            </div>

            <div class="p-8 space-y-8">
                <!-- Name -->
                <div class="space-y-3">
                    <label class="text-sm font-black text-foreground uppercase tracking-widest pl-1">Category Name</label>
                    <input 
                        type="text" 
                        wire:model="name"
                        placeholder="e.g. Artisanal Burgers"
                        class="w-full h-14 px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background focus:ring-4 focus:ring-orange-500/15 focus:border-orange-500/50 focus:outline-none transition-all font-bold text-lg"
                    >
                    @error('name') <span class="text-xs text-rose-500 font-black pl-1">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div class="space-y-3">
                    <label class="text-sm font-black text-foreground uppercase tracking-widest pl-1">Story / Description</label>
                    <textarea 
                        wire:model="description"
                        placeholder="What makes this category special?"
                        class="w-full h-32 p-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background focus:ring-4 focus:ring-orange-500/15 focus:border-orange-500/50 focus:outline-none transition-all resize-none text-base font-medium"
                    ></textarea>
                </div>

                <!-- Status Switch -->
                <div class="flex items-center justify-between p-6 bg-primary/5 rounded-3xl border border-primary/10">
                    <div class="space-y-1">
                        <span class="block text-sm font-black text-foreground uppercase tracking-wider">Visibility Status</span>
                        <p class="text-xs text-primary/70 font-bold">
                            {{ $is_active ? 'Live: Visible to customers' : 'Draft: Hidden from menu' }}
                        </p>
                    </div>
                    <button 
                        type="button" 
                        @click="$wire.set('is_active', !@js($is_active))"
                        class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 focus:outline-none shadow-sm {{ $is_active ? 'bg-primary' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform duration-300 ease-out {{ $is_active ? 'translate-x-6' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="flex gap-4 pt-4">
                    <button 
                        @click="showDialog = false"
                        class="flex-1 h-16 rounded-2xl border-2 border-border/50 font-black text-base hover:bg-muted transition-all active:scale-95"
                    >
                        Back
                    </button>
                    <button 
                        wire:click="save"
                        class="flex-[2] h-16 bg-gradient-to-r from-primary to-primary/80 text-white font-black text-base rounded-2xl shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all"
                    >
                        {{ $editingCategoryId ? 'Update Changes' : 'Confirm & Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Add Modal -->
    <div 
        x-show="showBulkDialog" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        x-cloak
    >
        <div 
            x-show="showBulkDialog"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-zinc-950/80"
            @click="showBulkDialog = false"
        ></div>

        <div 
            x-show="showBulkDialog"
            x-transition:enter="transition ease-out duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
            x-transition:enter-start="opacity-0 scale-90 translate-y-10"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="bg-card w-full max-w-xl rounded-[2.5rem] shadow-2xl relative overflow-hidden border border-border/60"
        >
            <div class="p-8 border-b border-border/40 bg-muted/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-foreground">Bulk Entry</h2>
                        <p class="text-sm text-muted-foreground mt-1 font-medium">Add multiple menu sections in seconds</p>
                    </div>
                    <button @click="showBulkDialog = false" class="p-3 bg-background hover:bg-muted text-muted-foreground rounded-2xl transition-all border border-border/50">
                        <i data-lucide="x" class="size-5"></i>
                    </button>
                </div>
            </div>

            <div class="p-8 space-y-8">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-black text-foreground uppercase tracking-widest pl-1">Category List</label>
                        <span 
                            x-show="bulkCount > 0"
                            class="text-[10px] font-black bg-primary text-white px-3 py-1 rounded-full uppercase tracking-tighter"
                            x-text="bulkCount + ' Detected'"
                        ></span>
                    </div>
                    <textarea 
                        wire:model="bulkInput"
                        placeholder="e.g. Desserts, Traditional Meals&#10;Seasonal Specials, Fresh Juices"
                        class="w-full h-56 p-6 rounded-[2rem] bg-muted/30 border border-border/50 focus:bg-background focus:ring-4 focus:ring-primary/15 focus:border-primary/50 focus:outline-none transition-all resize-none text-lg font-bold leading-relaxed text-foreground"
                    ></textarea>
                    <div class="flex gap-3 px-2">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center text-muted-foreground group">
                            <i data-lucide="info" class="w-5 h-5 group-hover:text-primary transition-colors"></i>
                        </div>
                        <p class="text-xs text-muted-foreground/80 font-medium italic">
                            Separate entries with commas or simply press Enter for each new name. We'll handle the rest.
                        </p>
                    </div>
                </div>

                <button 
                    wire:click="saveBulk"
                    class="w-full h-16 bg-gradient-to-r from-primary to-primary/80 text-white font-black text-lg rounded-2xl shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all"
                >
                    Process Everything
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
