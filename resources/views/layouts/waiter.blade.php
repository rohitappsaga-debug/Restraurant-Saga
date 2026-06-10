<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Waiter</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://unpkg.com/lucide@0.454.0"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        /* Smooth scroll management */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Premium root styling */
        body {
            overscroll-behavior-y: none;
        }

        /* Prevent icon flickering/vanishing */
        [data-lucide] {
            display: inline-block;
            min-width: 1em;
            min-height: 1em;
        }

        /* ABSOLUTE SUPPRESSION OF LIVEWIRE FAILURE INDICATOR */
        [id^="livewire-error"], .livewire-error, #livewire-error {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Hide the dark backdrop injected by Livewire during errors */
        div[style*="z-index: 100000"], div[style*="z-index: 99999"] {
            display: none !important;
        }
    </style>
</head>
<body class="bg-background text-foreground font-display antialiased overscroll-none overflow-hidden">
    
    

    <x-admin.toast />

    <!-- Mobile Layout Container -->
    <div class="flex flex-col h-screen overflow-hidden">
        
        <!-- Slot for Content -->
        {{ $slot }}

    </div>

    @livewireScripts
    <script>
        // Global Icon Refresh Logic with Debounce
        let iconTimeout;
        function refreshIcons() {
            if (iconTimeout) clearTimeout(iconTimeout);
            iconTimeout = setTimeout(() => {
                if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            }, 50);
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
            // Initial call
            refreshIcons();

            // v3 morph hook
            Livewire.hook('morph.updated', () => {
                refreshIcons();
            });

            // v3 navigation hook
            document.addEventListener('livewire:navigated', refreshIcons);

            // Listen for Item updates via Reverb
            if (window.Echo) {
                window.Echo.channel('orders')
                    .listen('.App.Events.ItemStatusUpdated', (e) => handleReadyEvent(e))
                    .listen('ItemStatusUpdated', (e) => handleReadyEvent(e));
            }

            function handleReadyEvent(e) {
                // Audio context handle
                const notificationAudio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                notificationAudio.play().catch(err => console.log('Audio play blocked:', err));

                // Show Visual Toast if the item is READY
                if (e.status === 'ready' || e.status?.value === 'ready') {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { 
                            type: 'success', 
                            message: `Ready: ${e.item_name} for Table ${e.table_number}` 
                        }
                    }));
                }

                // Force a refresh of all Livewire components on the page
                if (window.Livewire) {
                    window.Livewire.dispatch('$refresh');
                }
            }
        });

        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    preventDefault();
                    let message = 'A server error occurred. Please try again.';
                    if (status === 419) message = 'Session expired. Refreshing...';
                    if (status === 0) message = 'Network connection lost.';
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { type: 'error', message: message }
                    }));
                    if (status === 419) setTimeout(() => window.location.reload(), 2000);
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            // High-level patch to prevent Livewire crash on showModal
            if (window.HTMLDialogElement) {
                const originalShowModal = HTMLDialogElement.prototype.showModal;
                HTMLDialogElement.prototype.showModal = function() {
                    if (this.id && this.id.includes('livewire-error')) return;
                    try {
                        return originalShowModal.apply(this, arguments);
                    } catch (e) {
                        console.error('Handled dialog error:', e);
                    }
                };
            }
            refreshIcons();
        });
    </script>

</body>
</html>
