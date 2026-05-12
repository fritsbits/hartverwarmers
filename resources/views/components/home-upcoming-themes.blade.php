@props(['themes', 'month', 'themesByDate' => []])

@php
    $allInSameMonth = $themes->every(fn ($occ) => $occ->start_date->isSameMonth($month));
@endphp

@if($themes->isNotEmpty())
    <section class="bg-[var(--color-bg-cream)] border-y border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-14">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_24rem] gap-12 items-center">
                {{-- Left: heading + list + CTA --}}
                <div>
                    <span class="section-label">Op de kalender</span>
                    <h2 class="text-5xl font-heading font-bold mt-2 leading-none">Binnenkort</h2>

                    <ul class="mt-8 divide-y divide-[var(--color-border-light)] border-y border-[var(--color-border-light)]">
                        @foreach($themes as $occ)
                            @php($start = $occ->start_date->locale('nl_BE'))
                            @php($monthSlug = $start->format('Y-m'))
                            <li>
                                <a href="{{ route('themes.index', ['maand' => $monthSlug]) }}#thema-{{ $occ->theme->slug }}"
                                   class="group flex items-baseline gap-6 py-4 hover:bg-[var(--color-bg-accent-light)] -mx-3 px-3 rounded transition-colors">
                                    <span class="text-sm uppercase tracking-wider text-[var(--color-text-secondary)] {{ $allInSameMonth ? 'w-10' : 'w-20' }} shrink-0 tabular-nums">
                                        {{ $allInSameMonth ? $start->translatedFormat('j') : $start->translatedFormat('j M') }}
                                    </span>
                                    <span class="font-heading text-xl flex-1 text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)]">
                                        {{ $occ->theme->title }}
                                    </span>
                                    <span class="text-[var(--color-text-tertiary)] group-hover:text-[var(--color-primary)] transition-colors">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8">
                        <flux:button variant="filled" icon:trailing="arrow-right" :href="route('themes.index')">
                            Bekijk de hele themakalender
                        </flux:button>
                    </div>
                </div>

                {{-- Right: mini-cal --}}
                <div class="hidden lg:flex justify-center">
                    <x-theme-month-overview :month="$month" :themes-by-date="$themesByDate" />
                </div>
            </div>
        </div>
    </section>
@endif
