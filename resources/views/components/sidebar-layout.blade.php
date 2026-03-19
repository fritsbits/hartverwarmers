@props(['title', 'heading' => null, 'description' => null, 'sectionLabel' => null])


<x-layout :title="$title" bg-class="bg-[var(--color-bg-cream)]">

    <div class="py-8 sm:py-12">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">

            {{-- Sidebar navigation — collapses to horizontal scroll on mobile --}}
            <nav class="lg:w-56 shrink-0">
                <div class="lg:sticky lg:top-24">
                    {{-- Mobile: horizontal tab bar --}}
                    <div class="lg:hidden relative -mx-6 px-6">
                        <div class="overflow-x-auto scrollbar-hide pr-6">
                            <div class="flex items-center gap-1 pb-2 min-w-max">
                            <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('profile.show') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                <flux:icon name="user" variant="mini" class="size-4" />
                                Profiel
                            </a>
                            <a href="{{ route('profile.security') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('profile.security') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                <flux:icon name="lock-closed" variant="mini" class="size-4" />
                                Beveiliging
                            </a>
                            @if(auth()->user()->isAdmin())
                                <span class="w-px h-6 bg-[var(--color-border-light)] mx-1 shrink-0"></span>
                                <a href="{{ route('admin.design-system') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('admin.design-system') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="swatch" variant="mini" class="size-4" />
                                    Design
                                </a>
                                <a href="{{ route('admin.features') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('admin.features') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="flag" variant="mini" class="size-4" />
                                    Features
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="users" variant="mini" class="size-4" />
                                    Gebruikers
                                </a>
                                <a href="{{ route('admin.fiches.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('admin.fiches.*') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="document-text" variant="mini" class="size-4" />
                                    Fiches
                                </a>
                                <a href="{{ route('admin.health') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->routeIs('admin.health') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="signal" variant="mini" class="size-4" />
                                    Gezondheid
                                </a>
                                <a href="{{ route('admin.mails.show', 'verify-email') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->is('*/mails/verify-email') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="envelope" variant="mini" class="size-4" />
                                    Verificatie
                                </a>
                                <a href="{{ route('admin.mails.show', 'reset-password') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->is('*/mails/reset-password') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="envelope" variant="mini" class="size-4" />
                                    Ww reset
                                </a>
                                <a href="{{ route('admin.mails.show', 'welcome') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors {{ request()->is('*/mails/welcome') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)]' }}">
                                    <flux:icon name="envelope" variant="mini" class="size-4" />
                                    Welkomst
                                </a>
                            @endif
                            </div>
                        </div>
                        {{-- Scroll fade indicator --}}
                        <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-[var(--color-bg-cream)] to-transparent"></div>
                    </div>

                    {{-- Desktop: vertical navlist --}}
                    <div class="hidden lg:block">
                        <flux:navlist>
                            <flux:navlist.group heading="Profiel">
                                <flux:navlist.item href="{{ route('profile.show') }}" icon="user" :current="request()->routeIs('profile.show')">Persoonlijke info</flux:navlist.item>
                                <flux:navlist.item href="{{ route('profile.security') }}" icon="lock-closed" :current="request()->routeIs('profile.security')">Beveiliging</flux:navlist.item>
                            </flux:navlist.group>

                            @if(auth()->user()->isAdmin())
                                <flux:navlist.group heading="Admin" class="mt-4">
                                    <flux:navlist.item href="{{ route('admin.design-system') }}" icon="swatch" :current="request()->routeIs('admin.design-system')">Design Systeem</flux:navlist.item>
                                    <flux:navlist.item href="{{ route('admin.features') }}" icon="flag" :current="request()->routeIs('admin.features')">Features</flux:navlist.item>
                                    <flux:navlist.item href="{{ route('admin.users.index') }}" icon="users" :current="request()->routeIs('admin.users.*')">Gebruikers</flux:navlist.item>
                                    <flux:navlist.item href="{{ route('admin.fiches.index') }}" icon="document-text" :current="request()->routeIs('admin.fiches.*')">Fiches</flux:navlist.item>
                                    <flux:navlist.item href="{{ route('admin.health') }}" icon="signal" :current="request()->routeIs('admin.health')">Gezondheid</flux:navlist.item>
                                    <flux:navlist.group heading="E-mails" expandable :expanded="request()->routeIs('admin.mails*')">
                                        <flux:navlist.item href="{{ route('admin.mails.show', 'verify-email') }}" :current="request()->is('*/mails/verify-email')">Verificatie</flux:navlist.item>
                                        <flux:navlist.item href="{{ route('admin.mails.show', 'reset-password') }}" :current="request()->is('*/mails/reset-password')">Wachtwoord reset</flux:navlist.item>
                                        <flux:navlist.item href="{{ route('admin.mails.show', 'welcome') }}" :current="request()->is('*/mails/welcome')">Welkomstmail</flux:navlist.item>
                                    </flux:navlist.group>
                                </flux:navlist.group>
                            @endif
                        </flux:navlist>
                    </div>
                </div>
            </nav>

            {{-- Content area --}}
            <div class="min-w-0 flex-1">
                {{-- Page header --}}
                <div class="mb-8">
                    @if($sectionLabel)
                        <p class="section-label mb-1">{{ $sectionLabel }}</p>
                    @endif
                    <div class="flex items-baseline justify-between gap-4">
                        <h1 class="text-[var(--text-h2)]">{{ $heading ?? $title }}</h1>
                        {{ $headerAction ?? '' }}
                    </div>
                    @if($description)
                        <p class="text-[var(--color-text-secondary)] font-light mt-2">{{ $description }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>

        </div>
    </div>
</x-layout>
