@props(['title' => null, 'fullWidth' => false])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hartverwarmers' }} - Hartverwarmers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700|nanum-pen-script:400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-base)]">
    <!-- Navigation -->
    <x-nav />

    <!-- Flash Messages -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="max-w-6xl mx-auto px-6 mt-4">
            <div class="flex items-center justify-between gap-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="shrink-0 text-green-600 hover:text-green-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @isset($breadcrumbs)
            <div class="max-w-6xl mx-auto px-6 pt-8">
                <div class="flex items-center justify-between">
                    <flux:breadcrumbs>
                        {{ $breadcrumbs }}
                    </flux:breadcrumbs>
                    @isset($headerActions)
                        {{ $headerActions }}
                    @endisset
                </div>
            </div>
        @endisset

        @if($fullWidth)
            {{ $slot }}
        @else
            <div class="max-w-6xl mx-auto px-6">
                {{ $slot }}
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="mt-16">
        
        <!-- Bottom Footer -->
        <div class="bg-[var(--color-bg-base)] border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-8 text-sm text-[var(--color-text-secondary)]">
                    <span>&copy; {{ date('Y') }} Hartverwarmers</span>
                </div>
            </div>
        </div>
    </footer>

    <livewire:search />

    @fluxScripts
</body>
</html>
