@props(['title', 'heading' => null, 'description' => null, 'sectionLabel' => null])

<x-layout :title="$title" bg-class="bg-[var(--color-bg-cream)]">

    <div class="py-8 sm:py-12">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">

            {{-- Sidebar navigation --}}
            <nav class="lg:w-56 shrink-0">
                <div class="lg:sticky lg:top-8">
                    <flux:navlist>
                        <flux:navlist.group heading="Profiel">
                            <flux:navlist.item href="{{ route('profile.show') }}" icon="user" :current="request()->routeIs('profile.show')">Persoonlijke info</flux:navlist.item>
                            <flux:navlist.item href="{{ route('profile.security') }}" icon="lock-closed" :current="request()->routeIs('profile.security')">Beveiliging</flux:navlist.item>
                            <flux:navlist.item href="{{ route('profile.bookmarks') }}" icon="bookmark" :current="request()->routeIs('profile.bookmarks')">Favorieten</flux:navlist.item>
                            @php($newFicheComments = auth()->user()->newFicheCommentsCount())
                            <flux:navlist.item href="{{ route('profile.fiches') }}" icon="document-text" :current="request()->routeIs('profile.fiches')" :badge="$newFicheComments > 0 ? $newFicheComments : null">Fiches</flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.item href="{{ route('contributors.show', auth()->user()) }}" icon="arrow-top-right-on-square">Publiek profiel</flux:navlist.item>

                        @if(auth()->user()->isAdmin())
                            <flux:navlist.group heading="Admin" class="mt-4">
                                <flux:navlist.item href="{{ route('admin.design-system') }}" icon="swatch" :current="request()->routeIs('admin.design-system')">Design Systeem</flux:navlist.item>
                                <flux:navlist.item href="{{ route('admin.features') }}" icon="flag" :current="request()->routeIs('admin.features')">Features</flux:navlist.item>
                                <flux:navlist.item href="/pulse" icon="chart-bar">Pulse</flux:navlist.item>
                            </flux:navlist.group>
                        @endif
                    </flux:navlist>
                </div>

                <flux:separator class="lg:hidden mt-6" />
            </nav>

            {{-- Content area --}}
            <div class="min-w-0 flex-1">
                {{-- Page header --}}
                <div class="mb-8">
                    @if($sectionLabel)
                        <p class="section-label mb-1">{{ $sectionLabel }}</p>
                    @endif
                    <h2>{{ $heading ?? $title }}</h2>
                    @if($description)
                        <p class="text-[var(--color-text-secondary)] font-light mt-2">{{ $description }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>

        </div>
    </div>
</x-layout>
