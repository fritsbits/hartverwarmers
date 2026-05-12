@props(['themes', 'month', 'themesByDate' => []])

@php
    $allInSameMonth = $themes->every(fn ($occ) => $occ->start_date->isSameMonth($month));
@endphp

@if($themes->isNotEmpty())
    <section class="bg-[var(--color-bg-cream)] border-y border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_24rem] gap-12 items-center">
                {{-- Left: heading + list --}}
                <div>
                    <div class="flex items-baseline justify-between gap-4 flex-wrap mb-10">
                        <div>
                            <span class="section-label">Op de kalender</span>
                            <h2 class="text-3xl mt-1 text-balance">Plan deze dagen alvast in</h2>
                        </div>
                        <a href="{{ route('themes.index') }}" class="cta-link shrink-0">Alle themadagen</a>
                    </div>

                    <ul class="divide-y divide-[var(--color-border-light)] border-y border-[var(--color-border-light)]">
                        @foreach($themes as $occ)
                            @php($start = $occ->start_date->locale('nl_BE'))
                            @php($monthSlug = $start->format('Y-m'))
                            @php($count = $occ->theme->fiches_count ?? 0)
                            <li>
                                <a href="{{ route('themes.index', ['maand' => $monthSlug]) }}#thema-{{ $occ->theme->slug }}"
                                   class="group flex items-baseline gap-6 py-4 hover:bg-[var(--color-bg-accent-light)] -mx-3 px-3 rounded transition-colors">
                                    <span class="text-sm uppercase tracking-wider text-[var(--color-text-secondary)] {{ $allInSameMonth ? 'w-10' : 'w-20' }} shrink-0 tabular-nums">
                                        {{ $allInSameMonth ? $start->translatedFormat('j') : $start->translatedFormat('j M') }}
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block font-heading text-xl text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] truncate">
                                            {{ $occ->theme->title }}
                                        </span>
                                        @if($count > 0)
                                            <span class="block text-xs text-[var(--color-text-secondary)] tabular-nums mt-0.5">
                                                {{ $count }} {{ $count === 1 ? 'activiteit' : 'activiteiten' }}
                                            </span>
                                        @endif
                                    </span>
                                    <span aria-hidden="true" class="text-[var(--color-text-tertiary)] group-hover:text-[var(--color-primary)] group-hover:translate-x-0.5 transition-[color,transform]">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Right: mini-cal --}}
                <div class="hidden lg:flex justify-center">
                    <x-theme-month-overview :month="$month" :themes-by-date="$themesByDate" />
                </div>
            </div>
        </div>
    </section>
@endif
