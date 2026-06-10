<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://unpkg.com/lucide@0.454.0"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* Rapid Theme Switch Support */
        .no-transitions *,
        .no-transitions *:before,
        .no-transitions *:after {
            transition: none !important;
        }

        /* Prevent icon flickering/vanishing during Livewire updates */
        [data-lucide] {
            display: inline-block;
            min-width: 1em;
            min-height: 1em;
        }
    </style>
</head>
<body class="min-h-screen bg-background text-foreground font-display antialiased" x-data="{ isSidebarOpen: false, isNotificationsOpen: false, isCommandPaletteOpen: false }">
    
    <!-- Sidebar -->
    <x-admin.sidebar />

    <!-- Notifications -->
    <x-admin.toast />
    <livewire:admin.notifications-panel />

    <!-- Main Content Wrapper -->
    <div class="flex flex-col min-h-screen transition-[padding] duration-300 lg:pl-[280px]">
        <!-- Header -->
        <x-admin.header />

        <!-- Command Palette -->
        <x-admin.command-palette />

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8 mt-16 pb-24 lg:pb-8 flex flex-col">
            <div class="w-full h-full max-w-full">
                {{ $slot }}
            </div>
        </main>

        <!-- Mobile Bottom Nav (Optional, kept for now) -->
        <x-admin.bottom-nav />
    </div>

    @livewireScripts
    <script>
        // Global Icon Refresh Logic with Debounce
        if (typeof window.refreshIcons !== 'function') {
            let iconTimeout;
            window.refreshIcons = function() {
                if (iconTimeout) clearTimeout(iconTimeout);
                iconTimeout = setTimeout(() => {
                    if (window.lucide && typeof lucide.createIcons === 'function') {
                        lucide.createIcons();
                    }
                }, 50);
            };
        }


        function initThemeStore() {
            if (!window.Alpine) return;
            if (Alpine.store('theme')) return;

            Alpine.store('theme', {
                current: localStorage.getItem('rms_admin_theme') || '{{ auth()->user()->theme ?? 'light' }}',
                init() { 
                    this.apply(); 
                },
                toggle() {
                    this.current = this.current === 'light' ? 'dark' : 'light';
                    this.apply();
                    if (window.Livewire) {
                        window.Livewire.dispatch('theme-persisted', { theme: this.current });
                    }
                },
                set(theme) {
                    this.current = theme;
                    this.apply();
                },
                apply() {
                    document.documentElement.classList.add('no-transitions');
                    document.documentElement.classList.remove('light', 'dark');
                    document.documentElement.classList.add(this.current);
                    localStorage.setItem('rms_admin_theme', this.current);
                    requestAnimationFrame(() => {
                        document.documentElement.classList.remove('no-transitions');
                    });
                    if (window.refreshIcons) window.refreshIcons();
                }
            });
        }

        // Initialize on various events to ensure it's ready
        document.addEventListener('alpine:init', initThemeStore);
        document.addEventListener('livewire:init', initThemeStore);
        document.addEventListener('livewire:navigated', () => {
            initThemeStore();
            if (window.Alpine && Alpine.store('theme')) {
                Alpine.store('theme').apply();
            }
            if (window.refreshIcons) window.refreshIcons();
        });
        
        if (window.Alpine) {
            initThemeStore();
        }

        document.addEventListener('livewire:initialized', () => {
            if (window.refreshIcons) window.refreshIcons();

            // Listen for theme updates from backend
            Livewire.on('theme-updated', (event) => {
                const theme = typeof event === 'string' ? event : (event.theme || event[0]?.theme);
                if (theme) {
                    const store = Alpine.store('theme');
                    if (store && store.current !== theme) {
                        store.set(theme);
                    }
                }
            });

            // Payment Notification Listener
            @php
                $settings = \App\Models\Setting::first();
                $paymentEnabled = $settings?->notification_preferences['payment'] ?? true;
            @endphp

            const paymentEnabled = @json($paymentEnabled);

            if (window.Echo && paymentEnabled) {
                window.Echo.channel('payments')
                    .listen('PaymentReceived', (e) => {
                        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                        audio.play().catch(err => console.log('Audio play blocked:', err));
                        
                        if (window.showToast) {
                            showToast('Payment Received: ' + e.amount + ' via ' + e.method, 'success');
                        }
                    });
            }

            Livewire.hook('morph.updated', () => {
                if (window.refreshIcons) window.refreshIcons();
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (window.refreshIcons) window.refreshIcons();
        });
    </script>

    @stack('scripts')
</body>
</html>
