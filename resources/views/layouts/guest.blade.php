<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    <script>
        (function () {
            const theme = localStorage.getItem('rms_guest_theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            } else {
            document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@0.454.0"></script>
    
    @livewireStyles
</head>
<body class="min-h-screen bg-background text-foreground font-display antialiased overflow-x-hidden selection:bg-primary/20">
    
    {{ $slot }}

    @livewireScripts
    <script>
        // Global Icon Refresh Logic
        function refreshIcons() {
            if (window.lucide && typeof lucide.createIcons === 'function') {
                lucide.createIcons();
            }
        }

        document.addEventListener('livewire:navigated', refreshIcons);
        
        // Initial load
        document.addEventListener('DOMContentLoaded', refreshIcons);

        // Fallback for immediate execution if script loads late
        refreshIcons();
    </script>

</body>
</html>
