<div>
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-4 sm:pt-12">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Ontdek</span>
                <h1 class="text-3xl sm:text-4xl md:text-5xl mt-1">Alle activiteiten</h1>
                <p class="text-lg text-[var(--color-text-secondary)] mt-3 font-light leading-relaxed">
                    <span class="font-semibold text-[var(--color-text-primary)]">{{ $this->totalCount }} activiteiten</span> van collega's uit Vlaamse woonzorgcentra, de nieuwste bovenaan.
                    Liever per soort bladeren? <a href="{{ route('initiatives.index') }}" class="font-semibold text-[var(--color-primary)] hover:text-[var(--color-primary-hover)]">Bekijk de initiatieven</a>
                </p>
            </div>
        </div>
    </section>

    {{-- Toolbar --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2.5">
                <label class="relative flex-[1_1_100%] sm:flex-[1_1_240px] sm:max-w-xs">
                    <span class="sr-only">Zoek in alle activiteiten</span>
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-[var(--color-text-secondary)] pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input type="search" wire:model.live.debounce.300ms="q" placeholder="Zoek op titel of beschrijving" autocomplete="off"
                           class="w-full h-11 rounded-[10px] border border-[var(--color-border-light)] bg-white pl-10 pr-3.5 text-base text-[var(--color-text-primary)] placeholder:text-[var(--color-text-secondary)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]">
                </label>

                <x-filter-dropdown label="Initiatief" :selected="count($initiatief)">
                    @foreach($this->initiativeOptions as $option)
                        <label wire:key="ini-{{ $option['slug'] }}" @class(['flex items-center gap-2.5 w-full min-h-10 px-2.5 py-2 rounded-lg text-[15px] cursor-pointer hover:bg-[var(--color-bg-cream)] focus-within:bg-[var(--color-bg-cream)]', 'opacity-45' => $option['count'] === 0 && ! in_array($option['slug'], $initiatief, true)])>
                            <input type="checkbox" wire:model.live="initiatief" value="{{ $option['slug'] }}" class="h-[18px] w-[18px] shrink-0 rounded accent-[var(--color-primary)] cursor-pointer">
                            <span class="flex-1 min-w-0">{{ $option['title'] }}</span>
                            <span class="text-[13px] text-[var(--color-text-secondary)] tabular-nums">{{ $option['count'] }}</span>
                        </label>
                    @endforeach
                </x-filter-dropdown>

                @if($this->goalsEnabled)
                    <x-filter-dropdown label="Doel" :selected="count($doel)">
                        @foreach($this->goalOptions as $option)
                            <label wire:key="doel-{{ $option['slug'] }}" @class(['flex items-center gap-2.5 w-full min-h-10 px-2.5 py-2 rounded-lg text-[15px] cursor-pointer hover:bg-[var(--color-bg-cream)] focus-within:bg-[var(--color-bg-cream)]', 'opacity-45' => $option['count'] === 0 && ! in_array($option['slug'], $doel, true)])>
                                <input type="checkbox" wire:model.live="doel" value="{{ $option['slug'] }}" class="h-[18px] w-[18px] shrink-0 rounded accent-[var(--color-primary)] cursor-pointer">
                                <span class="grid place-content-center h-[22px] w-[22px] shrink-0 rounded-md bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] font-heading font-bold text-[13px]">{{ $option['letter'] }}</span>
                                <span class="flex-1 min-w-0">{{ $option['keyword'] }}</span>
                                <span class="text-[13px] text-[var(--color-text-secondary)] tabular-nums">{{ $option['count'] }}</span>
                            </label>
                        @endforeach
                    </x-filter-dropdown>
                @endif

                <button type="button" wire:click="$toggle('diamantjes')" aria-pressed="{{ $diamantjes ? 'true' : 'false' }}"
                        @class([
                            'inline-flex items-center gap-2 h-11 rounded-full border px-4 text-[15px] font-medium cursor-pointer transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]',
                            'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] text-[var(--color-primary-hover)]' => $diamantjes,
                            'border-[var(--color-border-light)] bg-white text-[var(--color-text-primary)] hover:border-[var(--color-border-hover)]' => ! $diamantjes,
                        ])>
                    <svg class="h-4 w-4 {{ $diamantjes ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M6 3h12l4 6-10 12L2 9z" /><path d="M2 9h20" /></svg>
                    Diamantjes
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                <div class="text-[15px] text-[var(--color-text-secondary)] max-sm:basis-full max-sm:order-2" aria-live="polite">
                    @if($this->hasActiveFilters())
                        <b class="font-semibold text-[var(--color-text-primary)] tabular-nums">{{ $this->resultCount }}</b> {{ $this->resultCount === 1 ? 'activiteit' : 'activiteiten' }} gevonden
                    @else
                        <b class="font-semibold text-[var(--color-text-primary)] tabular-nums">{{ $this->resultCount }}</b> activiteiten
                    @endif
                </div>

                @if($this->hasActiveFilters())
                    <div class="flex flex-wrap items-center gap-2 max-sm:order-3">
                        @foreach($this->initiatives->whereIn('slug', $initiatief) as $selected)
                            <span wire:key="pill-ini-{{ $selected->slug }}" class="inline-flex items-center gap-1.5 rounded-full border border-[var(--color-border-light)] bg-white py-1 pl-3 pr-1.5 text-sm">
                                {{ $selected->title }}
                                <button type="button" wire:click="removeFilter('initiatief', '{{ $selected->slug }}')" aria-label="Verwijder filter {{ $selected->title }}" class="grid place-content-center h-[22px] w-[22px] rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)] hover:bg-[var(--color-border-light)] hover:text-[var(--color-text-primary)] cursor-pointer">&times;</button>
                            </span>
                        @endforeach
                        @if($this->goalsEnabled)
                            @foreach(array_intersect_key($this->goalFacets, array_flip($doel)) as $facet)
                                <span wire:key="pill-doel-{{ $facet['slug'] }}" class="inline-flex items-center gap-1.5 rounded-full border border-[var(--color-border-light)] bg-white py-1 pl-3 pr-1.5 text-sm">
                                    {{ $facet['keyword'] }}
                                    <button type="button" wire:click="removeFilter('doel', '{{ $facet['slug'] }}')" aria-label="Verwijder filter {{ $facet['keyword'] }}" class="grid place-content-center h-[22px] w-[22px] rounded-full bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)] hover:bg-[var(--color-border-light)] hover:text-[var(--color-text-primary)] cursor-pointer">&times;</button>
                                </span>
                            @endforeach
                        @endif
                        <button type="button" wire:click="clearFilters" class="px-0.5 py-1 text-sm font-semibold text-[var(--color-primary)] hover:text-[var(--color-primary-hover)] cursor-pointer">Wis alle filters</button>
                    </div>
                @endif

                <div class="sm:ml-auto inline-flex gap-0.5 rounded-[10px] bg-[var(--color-bg-subtle)] p-[3px] max-sm:w-full max-sm:order-1" role="group" aria-label="Sorteer">
                    @foreach(['nieuwste' => 'Nieuwste', 'bedankt' => 'Meest bedankt', 'verras' => 'Verras me'] as $key => $label)
                        <button type="button" wire:click="toggleSort('{{ $key }}')" aria-pressed="{{ $sort === $key ? 'true' : 'false' }}"
                                @class([
                                    'min-h-9 rounded-lg px-3.5 text-sm font-medium cursor-pointer max-sm:flex-1 max-sm:px-1.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]',
                                    'bg-white text-[var(--color-text-primary)] shadow-sm' => $sort === $key,
                                    'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]' => $sort !== $key,
                                ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Results --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-2 pb-20 min-h-[60vh]">
            @if($this->resultCount === 0)
                <div class="text-center py-16 text-[var(--color-text-secondary)]">
                    <h2 class="text-2xl text-[var(--color-text-primary)] mb-2">Geen activiteiten gevonden</h2>
                    <p>Probeer een ander zoekwoord of haal een filter weg.</p>
                    <button type="button" wire:click="clearFilters" class="mt-5 rounded-full border border-[var(--color-border-hover)] bg-white px-7 py-3 font-semibold hover:border-[var(--color-primary)] hover:text-[var(--color-primary-hover)] cursor-pointer">Wis alle filters</button>
                </div>
            @else
                @php $grouped = $this->isGrouped(); $currentMonth = null; @endphp
                @foreach($this->fiches as $fiche)
                    @php $monthKey = $fiche->created_at->format('Y-m'); @endphp
                    @if($grouped && $monthKey !== $currentMonth)
                        @if($currentMonth !== null)
                            </div>
                        @endif
                        @php $currentMonth = $monthKey; $monthTotal = $this->monthCounts[$monthKey] ?? 0; @endphp
                        <div wire:key="month-{{ $monthKey }}" class="flex items-baseline gap-3 mt-9 first:mt-4 mb-3.5">
                            <h2 class="text-xl sm:text-2xl">{{ $this->monthLabel($fiche->created_at) }}</h2>
                            <span class="text-sm text-[var(--color-text-secondary)]">{{ $monthTotal }} {{ $monthTotal === 1 ? 'activiteit' : 'activiteiten' }}</span>
                            <span class="flex-1 h-px bg-[var(--color-border-light)] self-center"></span>
                        </div>
                        <div class="grid gap-[18px] sm:grid-cols-2 lg:grid-cols-3 [&>div>a]:h-full [&_.fiche-card]:h-full [&_.fiche-card-body]:h-full [&_.fiche-card-body]:flex [&_.fiche-card-body]:flex-col [&_.fiche-card-body>:last-child]:mt-auto">
                    @elseif(! $grouped && $loop->first)
                        <div class="grid gap-[18px] sm:grid-cols-2 lg:grid-cols-3 mt-4 [&>div>a]:h-full [&_.fiche-card]:h-full [&_.fiche-card-body]:h-full [&_.fiche-card-body]:flex [&_.fiche-card-body]:flex-col [&_.fiche-card-body>:last-child]:mt-auto">
                    @endif
                    <div wire:key="fiche-{{ $fiche->id }}" class="contents">
                        <x-fiche-card :fiche="$fiche" :show-initiative="true" :date-label="$this->dateLabel($fiche->created_at, ! $grouped)" />
                    </div>
                @endforeach
                </div>

                <div class="text-center mt-9">
                    @if($this->resultCount > $this->fiches->count())
                        <button type="button" wire:click="loadMore" class="rounded-full border border-[var(--color-border-hover)] bg-white px-7 py-3 text-base font-semibold hover:border-[var(--color-primary)] hover:text-[var(--color-primary-hover)] cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]">Toon meer activiteiten</button>
                        <p class="mt-2.5 text-sm text-[var(--color-text-secondary)]">{{ $this->fiches->count() }} van {{ $this->resultCount }} getoond</p>
                    @elseif($this->resultCount > \App\Livewire\FicheIndex::PAGE_SIZE)
                        <p class="text-sm text-[var(--color-text-secondary)]">Alle {{ $this->resultCount }} getoond</p>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
