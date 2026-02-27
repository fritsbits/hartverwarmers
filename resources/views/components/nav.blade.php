<nav x-data="{ mobileMenuOpen: false }" class="bg-[var(--color-bg-white)] sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-18">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[var(--color-primary)]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                    <span class="font-heading text-xl font-bold tracking-tight">hartverwarmers</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-1 ml-10">
                    <a href="{{ route('initiatives.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] rounded-lg hover:bg-[var(--color-bg-accent-light)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        Alle initiatieven
                    </a>

                    <div x-data="{ open: false, timeout: null }" @mouseenter="clearTimeout(timeout); open = true" @mouseleave="timeout = setTimeout(() => open = false, 150)" class="relative">
                        <button @click="open = !open" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] rounded-lg hover:bg-[var(--color-bg-accent-light)] transition-colors whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L22 12L12 22L2 12L12 2Z" />
                            </svg>
                            Doelen
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="open = false" class="absolute left-0 top-full mt-1 w-96 bg-white rounded-xl shadow-lg border border-[var(--color-border-light)] z-50">
                            <div class="px-4 pt-3 pb-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Zeven doelstellingen</span>
                            </div>

                            <div class="divide-y divide-[var(--color-border-light)]">
                                @foreach(config('diamant.facets') as $slug => $item)
                                    <a href="{{ route('goals.show', $slug) }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                        <span class="text-lg font-bold text-[var(--color-primary)] shrink-0 w-7 text-center" style="font-family: var(--font-heading)">{{ $item['letter'] }}</span>
                                        <div class="flex-1 min-w-0">
                                            <span class="font-semibold text-sm text-[var(--color-text-primary)]">{{ $item['keyword'] }}</span>
                                            <p class="text-xs text-[var(--color-text-secondary)]">{{ $item['ik_wil'] }}</p>
                                        </div>
                                        <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                    </a>
                                @endforeach
                            </div>

                            <div class="border-t border-[var(--color-border-light)] px-4 py-2">
                                <a href="{{ route('goals.index') }}" class="cta-link text-sm">Alle doelstellingen bekijken</a>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ open: false, timeout: null }" @mouseenter="clearTimeout(timeout); open = true" @mouseleave="timeout = setTimeout(() => open = false, 150)" class="relative">
                        <button @click="open = !open" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] rounded-lg hover:bg-[var(--color-bg-accent-light)] transition-colors whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                            Tools & inspiratie
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="open = false" class="absolute left-0 top-full mt-1 w-96 bg-white rounded-xl shadow-lg border border-[var(--color-border-light)] z-50">
                            <div class="px-4 pt-3 pb-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Leren & verdiepen</span>
                            </div>

                            <div class="divide-y divide-[var(--color-border-light)]">
                                <a href="{{ route('tools.videolessen') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                    <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-sm text-[var(--color-text-primary)]">Videolessen</span>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Leerzame video's over activiteitenbegeleiding</p>
                                    </div>
                                    <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                </a>

                                <a href="{{ route('tools.workshops') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                    <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                        </svg>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-sm text-[var(--color-text-primary)]">Workshops</span>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Praktische workshops voor in je organisatie</p>
                                    </div>
                                    <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                </a>

                                <a href="{{ route('tools.index') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                    <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                                        </svg>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-sm text-[var(--color-text-primary)]">Gidsen en tools</span>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Handige hulpmiddelen voor je dagelijkse praktijk</p>
                                    </div>
                                    <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auth Section -->
            <div class="flex items-center gap-3">
                <!-- Search icon -->
                <a href="{{ route('initiatives.index') }}" class="p-2 rounded-lg text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)] transition-colors" title="Zoeken">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </a>

                @auth
                    <flux:dropdown>
                        <flux:button variant="ghost" icon-trailing="chevron-down">
                            <div class="flex items-center gap-2">
                                @if(auth()->user()->avatar_path)
                                    <img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->first_name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold">
                                        {{ substr(auth()->user()->first_name, 0, 1) }}
                                    </div>
                                @endif
                                <span class="hidden sm:inline">{{ auth()->user()->first_name }}</span>
                            </div>
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item href="{{ route('profile.show') }}" icon="user">Mijn profiel</flux:menu.item>
                            <flux:menu.item href="{{ route('profile.bookmarks') }}" icon="bookmark">Mijn favorieten</flux:menu.item>
                            <flux:separator />
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Uitloggen</flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    @if(Route::has('login'))
                        <flux:button variant="ghost" href="{{ route('login') }}">Inloggen</flux:button>
                    @endif
                    @if(Route::has('register'))
                        <flux:button variant="primary" size="sm" href="{{ route('register') }}">Registreren</flux:button>
                    @endif
                @endauth

                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)]">
                    <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-cloak x-show="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-cloak x-show="mobileMenuOpen" x-transition class="lg:hidden border-t border-[var(--color-border-light)]">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="{{ route('initiatives.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                Alle initiatieven
            </a>
            <div x-data="{ open: false }" class="border-t border-[var(--color-border-light)] mt-2 pt-2">
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)]">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L22 12L12 22L2 12L12 2Z" />
                        </svg>
                        Doelen
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-cloak x-show="open" x-transition class="mt-1 space-y-1 pl-3">
                    @foreach(config('diamant.facets') as $slug => $item)
                        <a href="{{ route('goals.show', $slug) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
                            <span class="text-base font-bold text-[var(--color-primary)] w-6 text-center" style="font-family: var(--font-heading)">{{ $item['letter'] }}</span>
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $item['keyword'] }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('goals.index') }}" class="block px-3 py-2 cta-link text-sm">Alle doelstellingen bekijken</a>
                </div>
            </div>
            <div x-data="{ open: false }" class="border-t border-[var(--color-border-light)] mt-2 pt-2">
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)]">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                        Tools & inspiratie
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-cloak x-show="open" x-transition class="mt-1 space-y-1 pl-3">
                    <a href="{{ route('tools.videolessen') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span class="text-sm font-medium text-[var(--color-text-primary)]">Videolessen</span>
                    </a>
                    <a href="{{ route('tools.workshops') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        <span class="text-sm font-medium text-[var(--color-text-primary)]">Workshops</span>
                    </a>
                    <a href="{{ route('tools.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>
                        <span class="text-sm font-medium text-[var(--color-text-primary)]">Gidsen en tools</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
