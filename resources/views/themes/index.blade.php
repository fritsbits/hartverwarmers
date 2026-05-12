@php
    $prevMonth = $month->subMonth();
    $nextMonth = $month->addMonth();
    $prevMonthLabel = $prevMonth->locale('nl_BE')->translatedFormat('F Y');
    $nextMonthLabel = $nextMonth->locale('nl_BE')->translatedFormat('F Y');
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
    $formatRange = function ($occ) {
        $start = $occ->start_date->locale('nl_BE');
        if (! $occ->end_date || $occ->end_date->equalTo($occ->start_date)) {
            return $start->translatedFormat('j F');
        }
        return $start->translatedFormat('j F').' – '.$occ->end_date->locale('nl_BE')->translatedFormat('j F');
    };
    $groupedDayThemes = $dayThemes->groupBy(fn ($t) => optional($t->occurrences->first())->start_date?->format('Y-m-d'));
@endphp

<x-layout title="Themakalender — {{ $monthLabel }}" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-8">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Themakalender</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Themakalender</span>
            <h1 class="text-5xl mt-1 font-heading font-bold leading-tight">Thema's en speciale momenten</h1>
            <p class="text-base text-[var(--color-text-secondary)] mt-3 max-w-xl">
                Ontdek welke themadagen eraan komen en vind activiteiten om ermee aan de slag te gaan.
            </p>

            {{-- Month selector --}}
            <div class="mt-10 flex items-baseline gap-6 flex-wrap">
                <strong class="font-heading text-4xl text-[var(--color-text-primary)] leading-none">{{ $monthLabel }}</strong>
                <span class="flex items-baseline gap-5 text-base">
                    <a href="{{ route('themes.index', ['maand' => $prevMonth->format('Y-m')]) }}"
                       class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                        ← {{ $prevMonthLabel }}
                    </a>
                    <span class="text-[var(--color-border-hover)]">·</span>
                    <a href="{{ route('themes.index', ['maand' => $nextMonth->format('Y-m')]) }}"
                       class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                        {{ $nextMonthLabel }} →
                    </a>
                </span>
            </div>
        </div>
    </section>

    {{-- Jump strip --}}
    @if($groupedDayThemes->isNotEmpty() || $seasonThemes->isNotEmpty())
        <div class="bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-5 flex flex-wrap gap-x-6 gap-y-3 items-baseline text-sm">
                <span class="font-semibold uppercase tracking-widest text-xs text-[var(--color-text-tertiary)]">In deze maand</span>
                @foreach($seasonThemes as $theme)
                    <a href="#thema-{{ $theme->slug }}"
                       class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                        {{ $theme->title }}
                    </a>
                @endforeach
                @foreach($dayThemes as $theme)
                    @php($occ = $theme->occurrences->first())
                    <a href="#thema-{{ $theme->slug }}"
                       class="group inline-flex items-baseline gap-2 text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors">
                        @if($occ)
                            <span class="text-xs font-heading font-bold text-[var(--color-primary)] tabular-nums">{{ $occ->start_date->locale('nl_BE')->translatedFormat('j M') }}</span>
                        @endif
                        <span>{{ $theme->title }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Content --}}
    <section class="bg-[var(--color-bg-white)] border-t border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-20 space-y-20">

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

            {{-- Day themes, grouped by date --}}
            @forelse($groupedDayThemes as $dateKey => $themesOnDate)
                @php($firstOcc = $themesOnDate->first()->occurrences->first())
                <div class="space-y-12">
                    {{-- Shared date header --}}
                    <div class="flex items-baseline gap-4">
                        <span class="font-heading font-bold text-base uppercase tracking-widest text-[var(--color-primary)] tabular-nums">
                            {{ $firstOcc?->start_date->locale('nl_BE')->translatedFormat('j F') }}
                        </span>
                        <span class="flex-1 border-t border-[var(--color-border-light)] mt-1"></span>
                    </div>

                    @foreach($themesOnDate as $theme)
                        @php($occ = $theme->occurrences->first())
                        @php($isRange = $occ && $occ->end_date && ! $occ->end_date->equalTo($occ->start_date))
                        <article id="thema-{{ $theme->slug }}">
                            <div class="flex items-baseline gap-3 flex-wrap mb-3">
                                <h2 class="text-3xl font-heading font-bold leading-tight">{{ $theme->title }}</h2>
                                @if($isRange)
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)] whitespace-nowrap">
                                        t/m {{ $occ->end_date->locale('nl_BE')->translatedFormat('j F') }}
                                    </span>
                                @endif
                            </div>

                            @if($theme->description)
                                <p class="text-[var(--color-text-secondary)] max-w-2xl">{{ $theme->description }}</p>
                            @endif

                            @if($theme->fiches->isNotEmpty())
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                                    @foreach($theme->fiches as $fiche)
                                        <x-fiche-card :fiche="$fiche" />
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-6 rounded-lg border border-dashed border-[var(--color-border-hover)] bg-[var(--color-bg-cream)] px-6 py-5 max-w-2xl">
                                    <p class="text-[var(--color-text-secondary)] text-sm">
                                        Nog geen activiteiten gekoppeld aan dit thema.
                                    </p>
                                    <a href="{{ route('fiches.create') }}" class="cta-link mt-1.5 inline-flex text-sm">
                                        Heb jij een idee? Deel je activiteit!
                                    </a>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
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
                    ← {{ $prevMonthLabel }}
                </a>
                <a href="{{ route('themes.index', ['maand' => $nextMonth->format('Y-m')]) }}" class="cta-link">
                    {{ $nextMonthLabel }} →
                </a>
            </div>
        </div>
    </section>
</x-layout>
