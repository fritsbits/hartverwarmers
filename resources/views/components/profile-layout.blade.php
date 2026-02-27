@props(['title'])

<x-layout :title="$title">
    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('profile.show') }}">Profiel</flux:breadcrumbs.item>
        @if(! request()->routeIs('profile.show'))
            <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
        @endif
    </x-slot:breadcrumbs>

    <div class="py-12">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-8">
            <!-- Sidebar -->
            <aside>
                <div class="bg-[var(--color-bg-white)] rounded-xl border border-[var(--color-border-light)] p-6">
                    <!-- User info -->
                    <div class="flex flex-col items-center text-center mb-6">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->first_name }}" class="w-16 h-16 rounded-full object-cover mb-3">
                        @else
                            <div class="w-16 h-16 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl font-semibold mb-3">
                                {{ substr(auth()->user()->first_name, 0, 1) }}
                            </div>
                        @endif
                        <p class="font-semibold text-[var(--color-text-primary)]">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                        @if(auth()->user()->function_title)
                            <p class="text-sm text-[var(--color-text-secondary)]">{{ auth()->user()->function_title }}</p>
                        @endif
                    </div>

                    <!-- Navigation -->
                    <nav class="space-y-1">
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.show') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)] hover:text-[var(--color-primary)]' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Profiel
                        </a>

                        <a href="{{ route('profile.security') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.security') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)] hover:text-[var(--color-primary)]' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Beveiliging
                        </a>

                        <a href="{{ route('profile.bookmarks') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.bookmarks') ? 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' : 'text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)] hover:text-[var(--color-primary)]' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            Favorieten
                        </a>

                        <div class="border-t border-[var(--color-border-light)] my-2!"></div>

                        <a href="{{ route('contributors.show', auth()->user()) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)] hover:text-[var(--color-primary)] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Publiek profiel
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Content -->
            <div>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout>
