<nav x-data="{ mobileMenuOpen: false }" class="bg-[var(--color-bg-white)] border-b border-[var(--color-border-light)] sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="bg-[var(--color-primary)] p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-semibold">hartverwarmers</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-1 ml-8">
                    <a href="{{ route('activities.index') }}" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">Activiteiten</a>
                    <a href="{{ route('themes.index') }}" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">Kalender</a>
                    <a href="{{ route('authors.index') }}" class="px-3 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">Bijdragers</a>
                </div>
            </div>

            <!-- Auth Section -->
            <div class="flex items-center gap-2">
                @auth
                    <flux:dropdown>
                        <flux:button variant="ghost" icon-trailing="chevron-down">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            </div>
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item href="{{ route('profile.show') }}" icon="user">Mijn profiel</flux:menu.item>
                            <flux:menu.item href="{{ route('profile.bookmarks') }}" icon="bookmark">Mijn bookmarks</flux:menu.item>
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
                        <flux:button variant="primary" href="{{ route('register') }}">Registreren</flux:button>
                    @endif
                @endauth

                <!-- Dark mode toggle -->
                <flux:button variant="ghost" size="sm" x-data x-on:click="$flux.dark = !$flux.dark" class="ml-2">
                    <svg x-show="!$flux.dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="$flux.dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </flux:button>

                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-md text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)]">
                    <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="mobileMenuOpen" x-transition class="lg:hidden border-t border-[var(--color-border-light)]">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="{{ route('activities.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)]">Activiteiten</a>
            <a href="{{ route('themes.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)]">Kalender</a>
            <a href="{{ route('authors.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)]">Bijdragers</a>
        </div>
    </div>
</nav>
