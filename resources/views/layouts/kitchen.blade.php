<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Kitchen</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://unpkg.com/lucide@0.454.0"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* Rapid Theme Switch Support - Copied from admin layout to fix lag */
        .no-transitions *,
        .no-transitions *:before,
        .no-transitions *:after {
            transition: none !important;
        }

        /* Prevent icon flickering/vanishing */
        [data-lucide] {
            display: inline-block;
            min-width: 1em;
            min-height: 1em;
        }
    </style>
</head>
<body class="min-h-screen bg-background text-foreground font-display antialiased">
    
    <!-- Notifications -->
    <x-admin.toast />

    <!-- Main Content Wrapper (No Sidebar, No Header) -->
    <div class="flex flex-col min-h-screen">
        <!-- Main Content -->
        <main class="flex-1">
            <div class="w-full h-full max-w-full">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
    <script>
        // Global Icon Refresh Logic
        if (typeof window.refreshIcons !== 'function') {
            window.refreshIcons = function() {
                if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            };
        }

        function initThemeStore() {
            if (!window.Alpine) return;
            if (Alpine.store('theme')) return;

            Alpine.store('theme', {
                current: localStorage.getItem('rms_kitchen_theme') || '{{ auth()->user()->theme ?? 'light' }}',
                init() { this.apply(); },
                toggle() {
                    this.current = this.current === 'light' ? 'dark' : 'light';
                    this.apply();
                    window.Livewire.dispatch('theme-persisted', { theme: this.current });
                },
                set(theme) {
                    this.current = theme;
                    this.apply();
                },
                apply() {
                    document.documentElement.classList.add('no-transitions');
                    document.documentElement.classList.remove('light', 'dark');
                    document.documentElement.classList.add(this.current);
                    localStorage.setItem('rms_kitchen_theme', this.current);
                    requestAnimationFrame(() => {
                        document.documentElement.classList.remove('no-transitions');
                    });
                    setTimeout(window.refreshIcons, 50);
                }
            });
        }

        document.addEventListener('alpine:init', initThemeStore);
        if (window.Alpine) { initThemeStore(); }

        document.addEventListener('livewire:initialized', () => {
            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
            window.refreshIcons();

            Livewire.on('theme-updated', (event) => {
                const theme = typeof event === 'string' ? event : event.theme;
                const store = Alpine.store('theme');
                if (store && store.current !== theme) {
                    store.set(theme);
                }
            });

            // Listen for new KOTs via Reverb
            if (window.Echo) {
                window.Echo.channel('kitchen')
                    .listen('.KOTCreated', (e) => {
                        audio.play().catch(e => console.log('Audio play blocked by browser policy.'));
                    });
            }

            Livewire.hook('morph.updated', () => {
                setTimeout(window.refreshIcons, 10);
            });
        });

        document.addEventListener('livewire:navigated', () => {
            initThemeStore();
            if (window.Alpine && Alpine.store('theme')) {
                Alpine.store('theme').apply();
            }
            if (window.refreshIcons) window.refreshIcons();
        });

        document.addEventListener('DOMContentLoaded', window.refreshIcons);
    </script>

</body>
</html>
