<x-layout title="Downloads & favorieten" bg-class="bg-[var(--color-bg-cream)]">
    <div class="py-8 sm:py-12">
        @if($isGuest)
            {{-- Anonymous conversion CTA --}}
            <div class="max-w-lg mx-auto text-center py-16">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <flux:icon name="arrow-down-tray" class="size-12 text-[var(--color-border-light)]" />
                    <flux:icon name="bookmark" class="size-12 text-[var(--color-border-light)]" />
                </div>
                <h1 class="text-[var(--text-h2)] mb-3">Bewaar je favoriete fiches</h1>
                <p class="text-[var(--color-text-secondary)] font-light mb-8">
                    Sla inspirerende fiches op als favoriet en download ze om later te gebruiken. Zo heb je altijd ideeën bij de hand.
                </p>
                <flux:button variant="primary" href="{{ route('register') }}">Maak een gratis account</flux:button>
                <p class="mt-4 text-sm text-[var(--color-text-secondary)]">
                    Al een account? <a href="{{ route('login') }}" class="cta-link">Log in</a>
                </p>
            </div>
        @else
            {{-- Page header --}}
            <div class="mb-8">
                <p class="section-label mb-1">Ontdek</p>
                <h1 class="text-[var(--text-h2)]">Downloads & favorieten</h1>
                <p class="text-[var(--color-text-secondary)] font-light mt-2">Fiches die je hebt gedownload of als favoriet opgeslagen.</p>
            </div>

            {{-- Two-column layout --}}
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                {{-- Downloads column (wider) --}}
                <div class="lg:flex-[3]">
                    <p class="section-label mb-3 flex items-center gap-2">
                        <flux:icon name="arrow-down-tray" variant="mini" class="size-4" />
                        Downloads
                        <span class="bg-[var(--color-bg-subtle)] rounded-full px-2 py-0.5 text-xs font-normal">{{ $downloads->count() }}</span>
                    </p>

                    @if($downloads->isEmpty())
                        <div class="text-center py-12">
                            <flux:icon name="arrow-down-tray" class="size-12 mx-auto text-[var(--color-border-light)] mb-3" />
                            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches gedownload.</flux:text>
                            <flux:button variant="ghost" href="{{ route('initiatives.index') }}">Ontdek initiatieven</flux:button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($downloads as $fiche)
                                <div class="fiche-list-item group">
                                    <div class="fiche-list-icon">
                                        <flux:icon name="arrow-down-tray" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2">
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">{{ $fiche->title }}</a>
                                            @if($fiche->has_diamond)
                                                <x-diamond-badge class="shrink-0 mt-0.5" />
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)] truncate">
                                            @if($fiche->initiative)
                                                <span>{{ $fiche->initiative->title }}</span>
                                                <span class="text-[var(--color-border-light)]">&middot;</span>
                                            @endif
                                            @if($fiche->user)
                                                <span>{{ $fiche->user->first_name }} {{ $fiche->user->last_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-[var(--color-border-hover)] group-hover:text-[var(--color-primary)] transition-colors" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Favorieten column (narrower) --}}
                <div class="lg:flex-[2]">
                    <p class="section-label mb-3 flex items-center gap-2">
                        <flux:icon name="bookmark" variant="mini" class="size-4" />
                        Favorieten
                        <span class="bg-[var(--color-bg-subtle)] rounded-full px-2 py-0.5 text-xs font-normal">{{ $bookmarks->count() }}</span>
                    </p>

                    @if($bookmarks->isEmpty())
                        <div class="text-center py-12">
                            <flux:icon name="bookmark" class="size-12 mx-auto text-[var(--color-border-light)] mb-3" />
                            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches als favoriet opgeslagen.</flux:text>
                            <flux:button variant="ghost" href="{{ route('initiatives.index') }}">Ontdek initiatieven</flux:button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($bookmarks as $fiche)
                                <div class="fiche-list-item group">
                                    <div class="fiche-list-icon">
                                        <flux:icon name="bookmark" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2">
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">{{ $fiche->title }}</a>
                                            @if($fiche->has_diamond)
                                                <x-diamond-badge class="shrink-0 mt-0.5" />
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)] truncate">
                                            @if($fiche->initiative)
                                                <span>{{ $fiche->initiative->title }}</span>
                                                <span class="text-[var(--color-border-light)]">&middot;</span>
                                            @endif
                                            @if($fiche->user)
                                                <span>{{ $fiche->user->first_name }} {{ $fiche->user->last_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-[var(--color-border-hover)] group-hover:text-[var(--color-primary)] transition-colors" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layout>
