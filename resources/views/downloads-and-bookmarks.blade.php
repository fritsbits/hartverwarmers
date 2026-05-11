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
                <div class="lg:flex-[3]" x-data="{ filter: 'all' }">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <p class="section-label flex items-center gap-2">
                            <flux:icon name="arrow-down-tray" variant="mini" class="size-4" />
                            Downloads
                            <span class="bg-[var(--color-bg-subtle)] rounded-full px-2 py-0.5 text-xs font-normal">{{ $downloads->count() }}</span>
                        </p>

                        @if($downloads->isNotEmpty())
                            <div class="flex items-center gap-2">
                                <button x-on:click="filter = 'all'"
                                        :class="filter === 'all' ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white text-[var(--color-text-primary)] border-[var(--color-border-light)] hover:border-[var(--color-primary)]'"
                                        class="px-3 py-1 rounded-full border text-xs font-medium transition-colors">
                                    Alle ({{ $downloads->count() }})
                                </button>
                                <button x-on:click="filter = 'unthanked'"
                                        :class="filter === 'unthanked' ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white text-[var(--color-text-primary)] border-[var(--color-border-light)] hover:border-[var(--color-primary)]'"
                                        class="px-3 py-1 rounded-full border text-xs font-medium transition-colors">
                                    Nog niet bedankt ({{ $outstandingThanksCount }})
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($downloads->isEmpty())
                        <div class="text-center py-12">
                            <flux:icon name="arrow-down-tray" class="size-12 mx-auto text-[var(--color-border-light)] mb-3" />
                            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches gedownload.</flux:text>
                            <flux:button variant="ghost" href="{{ route('initiatives.index') }}">Ontdek initiatieven</flux:button>
                        </div>
                    @else
                        @if($outstandingThanksCount === 0)
                            <div x-show="filter === 'unthanked'" x-cloak
                                 class="text-center py-8 px-4 rounded-xl bg-[var(--color-bg-cream)] text-[var(--color-text-secondary)] mb-3 text-sm">
                                ✨ Je bedankte iedereen die je downloadde. Maak een collega's dag — vertel hen over Hartverwarmers.
                            </div>
                        @endif

                        <div class="space-y-2">
                            @foreach($downloads as $fiche)
                                @php $isThanked = $thankedFicheIds->contains($fiche->id); @endphp
                                <div data-thanked="{{ $isThanked ? 'true' : 'false' }}"
                                     x-show="filter === 'all' || (filter === 'unthanked' && {{ $isThanked ? 'false' : 'true' }})"
                                     x-cloak
                                     class="fiche-list-item">
                                    <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
                                       class="flex flex-col gap-0.5 min-w-0 flex-1 group">
                                        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors truncate">{{ $fiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">
                                            @if($fiche->initiative){{ $fiche->initiative->title }}@endif
                                            @if($fiche->initiative && $fiche->user)<span class="text-[var(--color-border-light)]">&middot;</span>@endif
                                            @if($fiche->user){{ $fiche->user->full_name }}@endif
                                        </span>
                                    </a>
                                    <div class="shrink-0">
                                        @if($isThanked)
                                            <span class="inline-flex items-center gap-1 text-xs text-[var(--color-text-secondary)]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                Bedankt
                                            </span>
                                        @else
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}#kudos-and-bookmark"
                                               class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-[var(--color-primary)]/30 bg-white hover:bg-[var(--color-bg-accent-light)] text-xs font-medium text-[var(--color-primary)] transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                                                Bedank
                                            </a>
                                        @endif
                                    </div>
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
                                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                                    <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">
                                            @if($fiche->initiative){{ $fiche->initiative->title }}@endif
                                            @if($fiche->initiative && $fiche->user)<span class="text-[var(--color-border-light)]">&middot;</span>@endif
                                            @if($fiche->user){{ $fiche->user->full_name }}@endif
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layout>
