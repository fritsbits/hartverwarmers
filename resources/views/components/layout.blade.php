@props(['title' => null, 'description' => null, 'ogImage' => null, 'fullWidth' => false, 'bgClass' => null])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name', 'Hartverwarmers') }}</title>
    <meta name="description" content="{{ $description ?? 'Ontdek deugddoende activiteiten voor woonzorgcentra. Hartverwarmers is het platform waar activiteitenbegeleiders praktijkfiches delen.' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph -->
    @php
        $ogTitle = $title ? $title . ' — ' . config('app.name') : config('app.name', 'Hartverwarmers');
        $ogDesc = $description ?? 'Ontdek deugddoende activiteiten voor woonzorgcentra. Hartverwarmers is het platform waar activiteitenbegeleiders praktijkfiches delen.';
        $ogImg = $ogImage ?? asset('images/hero-bewoonster-en-verzorgster-babbelen.jpg');
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImg }}">
    <meta property="og:locale" content="nl_BE">
    <meta property="og:site_name" content="{{ config('app.name', 'Hartverwarmers') }}">

    <link rel="icon" type="image/svg+xml" href="/img/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700|nanum-pen-script:400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>
<body class="font-body antialiased min-h-screen flex flex-col bg-[var(--color-bg-base)]">
    <!-- Navigation -->
    <x-nav />

    <!-- Toast notifications -->
    <flux:toast position="top end" />

    @if(session('toast'))
        <div x-data x-init="$flux.toast(@js(session('toast')))"></div>
    @elseif(session('success'))
        <div x-data x-init="$flux.toast({ text: @js(session('success')), variant: 'success' })"></div>
    @endif

    <!-- Main Content -->
    <main class="flex-1 {{ $bgClass }}">
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
    <footer class="bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 pt-16 pb-8">
            {{-- Fat footer columns --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
                {{-- Brand + stats --}}
                <div class="col-span-2 md:col-span-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" viewBox="0 0 100 100">
                            <path d="M20,5 L2,36 L50,97 L98,36 L80,5 L50,22 Z" fill="#e8764b"/>
                            <line x1="2" y1="36" x2="98" y2="36" stroke="rgba(255,255,255,0.2)" stroke-width="2.5" stroke-linecap="round"/><line x1="14" y1="51.25" x2="86" y2="51.25" stroke="rgba(255,255,255,0.2)" stroke-width="2" stroke-linecap="round"/><line x1="20" y1="5" x2="50" y2="22" stroke="rgba(255,255,255,0.2)" stroke-width="1.75" stroke-linecap="round"/><line x1="80" y1="5" x2="50" y2="22" stroke="rgba(255,255,255,0.2)" stroke-width="1.75" stroke-linecap="round"/><line x1="50" y1="22" x2="50" y2="97" stroke="rgba(255,255,255,0.2)" stroke-width="1.75" stroke-linecap="round"/><path d="M29.6,10.4 L17.4,45.8 L50.0,83.3 L82.6,45.8 L70.4,10.4 L50.0,34.0 Z" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2.5" stroke-linejoin="round"/>
                        </svg>
                        <span class="font-heading text-lg font-bold tracking-tight">hartverwarmers</span>
                    </a>
                    <p class="text-sm text-[var(--color-text-secondary)] font-light leading-relaxed mb-4">Inspiratie voor activiteitenbegeleiders in de ouderenzorg.</p>

                    @isset($footerStats)
                        <div class="space-y-1 text-sm text-[var(--color-text-secondary)]">
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $footerStats['fiches_count'] }}</span> fiches</p>
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $footerStats['contributors_count'] }}</span> bijdragers</p>
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $footerStats['organisations_count'] }}</span> organisaties</p>
                        </div>
                    @endisset
                </div>

                {{-- Ontdek --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-widest text-[var(--color-text-primary)] mb-4">Ontdek</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('initiatives.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Alle initiatieven</a></li>
                        <li><a href="{{ route('themes.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Themakalender</a></li>
                        <li><a href="{{ route('contributors.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Bijdragers</a></li>
                        <li><a href="{{ route('fiches.ficheVanDeMaand') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Fiche van de maand</a></li>
                    </ul>
                </div>

                {{-- Leren --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-widest text-[var(--color-text-primary)] mb-4">Leren</h4>
                    <ul class="space-y-2.5 text-sm">
                        @feature('diamant-goals')
                        <li><a href="{{ route('goals.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">DIAMANT-kompas</a></li>
                        @endfeature
                        <li><a href="{{ route('tools.videolessen') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Videolessen</a></li>
                        <li><a href="{{ route('tools.workshops') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Workshops</a></li>
                        <li><a href="{{ route('tools.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Gidsen en tools</a></li>
                    </ul>
                </div>

                {{-- Meedoen --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-widest text-[var(--color-text-primary)] mb-4">Meedoen</h4>
                    <ul class="space-y-2.5 text-sm">
                        @guest
                            <li><a href="{{ route('register') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Registreer</a></li>
                            <li><a href="{{ route('login') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Inloggen</a></li>
                        @endguest
                        @auth
                            <li><a href="{{ route('fiches.create') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Nieuwe fiche schrijven</a></li>
                            <li><a href="{{ route('profile.show') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Mijn profiel</a></li>
                            <li><a href="{{ route('bookmarks.index') }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Mijn favorieten</a></li>
                        @endauth
                    </ul>
                </div>
            </div>

            {{-- Bottom bar --}}
            <div class="border-t border-[var(--color-border-light)] pt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-[var(--color-text-secondary)]">
                    <span>&copy; {{ date('Y') }} Hartverwarmers</span>
                    <div class="flex items-center gap-6">
                        <a href="{{ route('about') }}" class="hover:text-[var(--color-primary)] transition-colors">Over ons</a>
                        <a href="{{ route('legal.privacy') }}" class="hover:text-[var(--color-primary)] transition-colors">Privacybeleid</a>
                        <a href="{{ route('legal.terms') }}" class="hover:text-[var(--color-primary)] transition-colors">Gebruiksvoorwaarden</a>
                        <a href="{{ route('legal.copyright') }}" class="hover:text-[var(--color-primary)] transition-colors">Auteursrecht</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <livewire:search />

    <x-dev.queue-badge />

    @fluxScripts
</body>
</html>
