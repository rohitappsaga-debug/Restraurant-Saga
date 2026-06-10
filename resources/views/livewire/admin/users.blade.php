<div class="space-y-8" x-data="{ showAddDialog: @entangle('showAddDialog') }">
    <!-- Autofill Trap -->
    <div style="display: none;">
        <input type="text" name="prevent_autofill_email" autocomplete="off" />
        <input type="password" name="prevent_autofill_password" autocomplete="off" />
    </div>
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">User Management</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Manage staff accounts and permissions</p>
        </div>
        <button 
            @click="$wire.openCreate()"
            class="h-14 px-8 bg-primary text-white shadow-lg shadow-primary/20 rounded-2xl font-bold text-sm hover:scale-105 active:scale-95 transition-all flex items-center gap-3"
        >
            <i data-lucide="plus" class="size-5"></i>
            Add User
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 px-2">
        @php
            $statConfig = [
                ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'users', 'color' => 'slate'],
                ['label' => 'Active', 'value' => $stats['active'], 'icon' => 'check-circle', 'color' => 'emerald'],
                ['label' => 'Waiters', 'value' => $stats['waiters'], 'icon' => 'user-check', 'color' => 'blue'],
                ['label' => 'Admins', 'value' => $stats['admins'], 'icon' => 'shield-check', 'color' => 'purple'],
            ];
        @endphp

        @foreach($statConfig as $stat)
            <div class="p-8 bg-card border border-border/50 rounded-[2.5rem] shadow-sm group hover:border-primary/20 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">{{ $stat['label'] }}</p>
                    <i data-lucide="{{ $stat['icon'] }}" class="size-5 text-{{ $stat['color'] }}-500 opacity-50 group-hover:opacity-100 transition-opacity"></i>
                </div>
                <div class="text-3xl font-black text-foreground">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-col md:flex-row gap-4 px-2">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 size-5 text-muted-foreground opacity-40"></i>
            <input
                type="text"
                name="rms_search_field_no_autofill"
                id="rms_search_field_no_autofill"
                autocomplete="new-password"
                wire:model.live.debounce.300ms="search"
                placeholder="Search staff members..."
                class="pl-14 h-14 w-full bg-card border border-border/50 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-primary/10 transition-all outline-none"
            />
        </div>
        <div class="relative" x-data="{ open: false }">
            <button 
                @click="open = !open"
                class="h-14 px-6 rounded-2xl bg-card border border-border/50 text-sm font-bold flex items-center justify-between min-w-[200px] hover:border-primary/30 transition-all outline-none focus:ring-4 focus:ring-primary/10"
            >
                <span class="uppercase tracking-widest text-[10px]">
                    {{ [
                        'all' => 'All Users',
                        'active' => 'Active Only',
                        'inactive' => 'Inactive Only'
                    ][$statusFilter] ?? 'Status' }}
                </span>
                <i data-lucide="chevron-down" class="size-4 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
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
                @foreach(['all' => 'All Users', 'active' => 'Active Only', 'inactive' => 'Inactive Only'] as $val => $label)
                    <button 
                        @click="$wire.set('statusFilter', '{{ $val }}'); open = false"
                        class="w-full px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $statusFilter === $val ? 'text-primary bg-primary/5' : 'text-muted-foreground' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- User Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 px-2">
        @forelse($users as $user)
            @php
                $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('');
                $avatarColor = $this->getAvatarColor($user->name);
                $role = $user->role->value;
                $roleStyle = match($role) {
                    'admin' => 'bg-purple-500/10 text-purple-600 border-purple-500/20',
                    'waiter' => 'bg-blue-500/10 text-blue-600 border-blue-500/20',
                    'kitchen' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                    default => 'bg-slate-500/10 text-slate-600 border-slate-500/20',
                };
            @endphp
            <div wire:key="user-{{ $user->id }}" class="group relative p-6 bg-card border border-border/50 rounded-[2rem] shadow-sm hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 hover:-translate-y-1 flex items-center gap-5">
                <!-- Avatar -->
                <div 
                    class="size-16 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-inner group-hover:scale-105 transition-transform"
                    style="background-color: {{ $avatarColor }};"
                >
                    {{ strtoupper($initials) }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-foreground text-lg truncate pr-8">{{ $user->name }}</h3>
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity absolute top-6 right-6">
                            <button wire:click="openEdit('{{ $user->id }}')" class="size-8 rounded-lg bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-primary/10 hover:text-primary transition-all">
                                <i data-lucide="edit-3" class="size-4"></i>
                            </button>
                            <button 
                                wire:click="delete('{{ $user->id }}')" 
                                wire:confirm="Are you sure you want to remove this user?"
                                class="size-8 rounded-lg bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-rose-500/10 hover:text-rose-500 transition-all"
                            >
                                <i data-lucide="trash-2" class="size-4"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground truncate mb-4">{{ $user->email }}</p>
                    
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest {{ $roleStyle }}">
                            {{ $role }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full {{ $user->active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                            <span class="text-[10px] font-bold uppercase tracking-widest {{ $user->active ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $user->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center bg-card/40 border border-dashed border-border/50 rounded-[3rem]">
                <div class="size-20 bg-muted/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="search-x" class="size-10 text-muted-foreground/40"></i>
                </div>
                <h3 class="text-xl font-bold text-foreground">No users found</h3>
                <p class="text-muted-foreground mt-2 font-medium">Try adjusting your filters or search terms</p>
                @if($search || $statusFilter !== 'all')
                    <button 
                        wire:click="$set('search', ''); $set('statusFilter', 'all');" 
                        class="mt-6 px-6 py-2 bg-primary/10 text-primary rounded-xl text-xs font-black uppercase tracking-widest hover:bg-primary/20 transition-all"
                    >
                        Clear All Filters
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="px-2 pb-12">
        {{ $users->links() }}
    </div>

    <!-- Add/Edit Modal -->
    <div 
        x-show="showAddDialog" 
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-background/60 backdrop-blur-xl"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-cloak
    >
        <div 
            class="bg-card w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-border relative"
            @click.away="showAddDialog = false"
        >
            <div class="p-8 border-b border-border/50">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="{{ $editingUserId ? 'user-cog' : 'user-plus' }}" class="size-6"></i>
                    </div>
                    <button @click="showAddDialog = false" class="size-10 flex items-center justify-center rounded-xl bg-muted/50 hover:bg-muted transition-all">
                        <i data-lucide="x" class="size-5"></i>
                    </button>
                </div>
                <h2 class="text-2xl font-black text-foreground tracking-tight">{{ $editingUserId ? 'Edit User' : 'Add New User' }}</h2>
                <p class="text-sm text-muted-foreground font-medium mt-1">Configure staff profile and access level</p>
            </div>

            <div class="p-8 space-y-6">
                <!-- Name -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest ml-1">Full Name</label>
                    <input type="text" wire:model="name" placeholder="John Doe" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium">
                    @error('name') <span class="text-xs text-rose-500 ml-1">{{ $message }}</span> @enderror
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-foreground uppercase tracking-widest ml-1">Email Address</label>
                    <input type="email" wire:model="email" placeholder="john@example.com" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium">
                    @error('email') <span class="text-xs text-rose-500 ml-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest ml-1">Role</label>
                        <div class="relative" x-data="{ open: false }">
                            <button 
                                type="button"
                                @click="open = !open"
                                class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 font-bold uppercase text-[10px] tracking-widest flex items-center justify-between outline-none focus:ring-4 focus:ring-primary/10 transition-all hover:bg-muted/40"
                            >
                                <span>{{ strtoupper($role ?: 'Select Role') }}</span>
                                <i data-lucide="chevron-down" class="size-4 opacity-50 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div 
                                x-show="open" 
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                class="absolute bottom-full mb-2 left-0 w-full bg-card border border-border shadow-2xl rounded-2xl overflow-hidden z-[60] backdrop-blur-xl"
                                x-cloak
                            >
                                @foreach(\App\Enums\UserRole::cases() as $case)
                                    <button 
                                        type="button"
                                        @click="$wire.set('role', '{{ $case->value }}'); open = false"
                                        class="w-full px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 transition-colors {{ $role === $case->value ? 'text-primary bg-primary/5' : 'text-foreground' }}"
                                    >
                                        {{ strtoupper($case->value) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-foreground uppercase tracking-widest ml-1">Password {{ $editingUserId ? '(Optional)' : '' }}</label>
                        <input type="password" wire:model="password" placeholder="••••••••" class="h-14 w-full px-5 rounded-2xl bg-muted/30 border border-border/50 focus:bg-background transition-all outline-none focus:ring-4 focus:ring-primary/10 font-medium">
                        @error('password') <span class="text-xs text-rose-500 ml-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Status Toggle -->
                <div class="flex items-center justify-between p-6 bg-muted/20 rounded-2xl border border-border/50">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-xl bg-background border border-border flex items-center justify-center text-emerald-500">
                            <i data-lucide="power" class="size-5"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-foreground">Active Account</span>
                            <p class="text-[10px] text-muted-foreground font-medium">Enable system access</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="$wire.set('active', !@js($active))"
                        class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-4 border-transparent transition-colors duration-200 focus:outline-none {{ $active ? 'bg-emerald-500 shadow-lg shadow-emerald-500/20' : 'bg-muted' }}"
                    >
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-background shadow-lg transition duration-200 ease-in-out {{ $active ? 'translate-x-6' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="pt-4">
                    <button wire:click="save" class="w-full h-16 bg-primary text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                        <i data-lucide="{{ $editingUserId ? 'save' : 'user-plus' }}" class="size-4"></i>
                        {{ $editingUserId ? 'Update User' : 'Create User' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        document.addEventListener('livewire:initialized', () => {
            lucide.createIcons();
        });

        $wire.on('notify', () => {
            setTimeout(() => lucide.createIcons(), 50);
        });

        $wire.on('user-saved', () => {
             // Logic if needed
        });
    </script>
    @endscript
</div>
