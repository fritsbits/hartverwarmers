@props(['themes'])

@if($themes->isNotEmpty())
    <section class="bg-[var(--color-bg-cream)] border-y border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-14">
            <span class="section-label">Op de kalender</span>
            <h2 class="text-3xl font-heading font-bold mt-1">Binnenkort</h2>

            <ul class="mt-8 divide-y divide-[var(--color-border-light)] border-y border-[var(--color-border-light)]">
                @foreach($themes as $occ)
                    @php($start = $occ->start_date->locale('nl_BE'))
                    @php($monthSlug = $start->format('Y-m'))
                    <li>
                        <a href="{{ route('themes.index', ['maand' => $monthSlug]) }}#thema-{{ $occ->theme->slug }}"
                           class="group flex items-baseline gap-6 py-4 hover:bg-[var(--color-bg-accent-light)] -mx-3 px-3 rounded transition-colors">
                            <span class="text-sm uppercase tracking-wider text-[var(--color-text-secondary)] w-28 shrink-0">
                                {{ $start->translatedFormat('j F') }}
                            </span>
                            <span class="font-heading text-xl flex-1 text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)]">
                                {{ $occ->theme->title }}
                            </span>
                            <span class="text-[var(--color-text-tertiary)] group-hover:text-[var(--color-primary)] transition-colors">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('themes.index') }}" class="cta-link inline-flex mt-6">Bekijk de hele themakalender →</a>
        </div>
    </section>
@endif
