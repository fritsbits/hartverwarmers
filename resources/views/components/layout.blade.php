<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hartverwarmers' }} - Hartverwarmers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-base)]">
    <!-- Navigation -->
    <x-nav />

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-16">
        <!-- Stats & CTA Bar -->
        <div class="bg-[var(--color-bg-subtle)] border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <p class="font-bold text-[var(--color-text-primary)]">
                            {{ $footerStats['elaborations_count'] ?? 0 }} uitwerkingen
                            <span class="text-[var(--color-text-secondary)]">&middot;</span>
                            {{ $footerStats['contributors_count'] ?? 0 }} bijdragers
                            <span class="text-[var(--color-text-secondary)]">&middot;</span>
                            {{ $footerStats['organisations_count'] ?? 0 }} organisaties
                        </p>
                        <a href="{{ route('register') }}" class="cta-link text-sm mt-1 inline-flex">+ Heb je ook een uitwerking om te delen? Draag bij</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="bg-[var(--color-bg-base)] border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-8 text-sm text-[var(--color-text-secondary)]">
                    <a href="#" class="hover:text-[var(--color-primary)] transition-colors">Over Hartverwarmers</a>
                    <a href="#" class="hover:text-[var(--color-primary)] transition-colors">Contact</a>
                    <a href="#" class="hover:text-[var(--color-primary)] transition-colors">Nieuwsbrief</a>
                    <span>&copy; {{ date('Y') }} Hartverwarmers</span>
                </div>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
