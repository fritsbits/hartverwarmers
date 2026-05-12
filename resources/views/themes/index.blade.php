@php
    $prevMonth = $month->subMonth();
    $nextMonth = $month->addMonth();
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
    $formatRange = function ($occ) {
        $start = $occ->start_date->locale('nl_BE');
        if (! $occ->end_date || $occ->end_date->equalTo($occ->start_date)) {
            return $start->translatedFormat('j F');
        }
        return $start->translatedFormat('j F').' – '.$occ->end_date->locale('nl_BE')->translatedFormat('j F');
    };
@endphp

<x-layout title="Themakalender — {{ $monthLabel }}" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Themakalender</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Themakalender</span>
            <h1 class="text-5xl mt-1 font-heading font-bold">Thema's en speciale momenten</h1>
            <p class="text-xl text-[var(--color-text-secondary)] mt-4 max-w-2xl">
                Ontdek welke themadagen eraan komen en vind activiteiten om ermee aan de slag te gaan.
            </p>

            {{-- Month selector --}}
            <div class="mt-10 flex items-center justify-center gap-6 text-lg">
                <a href="{{ route('themes.index', ['maand' => $prevMonth->format('Y-m')]) }}"
                   class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                    ← {{ $prevMonth->locale('nl_BE')->translatedFormat('F Y') }}
                </a>
                <strong class="font-heading text-2xl">{{ $monthLabel }}</strong>
                <a href="{{ route('themes.index', ['maand' => $nextMonth->format('Y-m')]) }}"
                   class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                    {{ $nextMonth->locale('nl_BE')->translatedFormat('F Y') }} →
                </a>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16 space-y-12">

            {{-- Season banners --}}
            @foreach($seasonThemes as $theme)
                @php($occ = $theme->occurrences->first())
                <div id="thema-{{ $theme->slug }}"
                     class="bg-[var(--color-bg-accent-light)] border border-[var(--color-border-light)] rounded-lg px-6 py-5">
                    <div class="flex items-baseline justify-between gap-4 flex-wrap">
                        <h2 class="text-2xl font-heading font-bold">{{ $theme->title }}</h2>
                        @if($occ)
                            <span class="text-sm text-[var(--color-text-secondary)]">{{ $formatRange($occ) }}</span>
                        @endif
                    </div>
                    @if($theme->description)
                        <p class="mt-2 text-[var(--color-text-secondary)] line-clamp-3">{{ $theme->description }}</p>
                    @endif
                </div>
            @endforeach

            {{-- Day themes --}}
            @forelse($dayThemes as $theme)
                @php($occ = $theme->occurrences->first())
                @php($isRange = $occ && $occ->end_date && ! $occ->end_date->equalTo($occ->start_date))
                <article id="thema-{{ $theme->slug }}" class="space-y-4">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        @if($occ)
                            <span class="text-sm uppercase tracking-wider text-[var(--color-text-secondary)]">{{ $formatRange($occ) }}</span>
                        @endif
                        @if($isRange)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)]">meerdaags</span>
                        @endif
                    </div>

                    <h2 class="text-3xl font-heading font-bold">{{ $theme->title }}</h2>

                    @if($theme->description)
                        <p class="text-[var(--color-text-secondary)] line-clamp-3 max-w-2xl">{{ $theme->description }}</p>
                    @endif

                    @if($theme->fiches->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                            @foreach($theme->fiches as $fiche)
                                <x-fiche-card :fiche="$fiche" />
                            @endforeach
                        </div>
                    @else
                        <div class="bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] rounded-lg p-6">
                            <p class="text-[var(--color-text-secondary)]">
                                Nog geen activiteiten gekoppeld aan dit thema.
                            </p>
                            <a href="{{ route('fiches.create') }}" class="cta-link mt-3 inline-flex">
                                Heb jij een idee? Deel je activiteit!
                            </a>
                        </div>
                    @endif
                </article>
            @empty
                @if($seasonThemes->isEmpty())
                    <flux:card class="text-center py-12">
                        <flux:heading size="lg" class="mb-3 font-heading font-bold">Geen thema's voor {{ $monthLabel }}</flux:heading>
                        <flux:text class="text-[var(--color-text-secondary)]">
                            Probeer een andere maand of <a href="{{ route('themes.index') }}" class="cta-link">bekijk de huidige maand →</a>
                        </flux:text>
                    </flux:card>
                @endif
            @endforelse

            {{-- Footer nav --}}
            <div class="flex items-center justify-between pt-8 border-t border-[var(--color-border-light)] text-base">
                <a href="{{ route('themes.index', ['maand' => $prevMonth->format('Y-m')]) }}" class="cta-link">
                    ← {{ $prevMonth->locale('nl_BE')->translatedFormat('F Y') }}
                </a>
                <a href="{{ route('themes.index', ['maand' => $nextMonth->format('Y-m')]) }}" class="cta-link">
                    {{ $nextMonth->locale('nl_BE')->translatedFormat('F Y') }} →
                </a>
            </div>
        </div>
    </section>
</x-layout>
