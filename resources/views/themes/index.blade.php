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
    {{-- Hero + monthly intro (one band) --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-10">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Themakalender</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Themakalender</span>
            <h1 class="text-6xl mt-2 font-heading font-bold leading-none">{{ $monthLabel }}</h1>

            @if(! empty($monthIntro))
                <div class="mt-10 max-w-2xl">
                    <h2 class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $monthIntro['title'] }}</h2>
                    <p class="text-base text-[var(--color-text-secondary)] mt-3 leading-relaxed">{{ $monthIntro['intro'] }}</p>
                </div>
            @endif
        </div>
    </section>

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
                <div class="md:grid md:grid-cols-[5rem_1fr] md:gap-x-10 lg:gap-x-14">
                    {{-- Date stamp column --}}
                    <div class="mb-6 md:mb-0">
                        <x-theme-date-stamp :date="$firstOcc->start_date" />
                    </div>

                    {{-- Themes column --}}
                    <div class="space-y-14">
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
