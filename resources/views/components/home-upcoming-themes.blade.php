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
                    <div class="mb-10">
                        <span class="section-label">Op de kalender</span>
                        <h2 class="text-3xl mt-1 text-balance">Plan deze dagen alvast in</h2>
                    </div>

                    <ul class="divide-y divide-[var(--color-border-light)]">
                        @foreach($themes as $occ)
                            @php($start = $occ->start_date->locale('nl_BE'))
                            @php($monthSlug = $start->format('Y-m'))
                            @php($count = $occ->theme->fiches_count ?? 0)
                            <li>
                                <a href="{{ route('themes.index', ['maand' => $monthSlug]) }}#thema-{{ $occ->theme->slug }}"
                                   class="group flex items-center gap-6 py-4 hover:bg-[var(--color-bg-accent-light)] -mx-3 px-3 rounded transition-colors">
                                    <div class="shrink-0 flex flex-col items-center justify-center rounded-full bg-[var(--color-bg-white)] gap-[4px]"
                                         style="width:55px;height:55px;padding:12px 6px;">
                                        <span class="font-body text-[10px] font-light leading-none text-[var(--color-text-secondary)] uppercase tracking-wide">
                                            {{ strtoupper(mb_substr($start->translatedFormat('l'), 0, 3)) }}
                                        </span>
                                        <span class="font-heading font-bold text-[30px] leading-none text-[var(--color-primary)]">
                                            {{ $start->translatedFormat('j') }}
                                        </span>
                                    </div>
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

                {{-- Right: mini-cal + CTA --}}
                <div class="hidden lg:flex flex-col items-center gap-6">
                    <x-theme-month-overview :month="$month" :themes-by-date="$themesByDate" />
                    <a href="{{ route('themes.index') }}" class="cta-link">Alle themadagen</a>
                </div>
            </div>
        </div>
    </section>
@endif
