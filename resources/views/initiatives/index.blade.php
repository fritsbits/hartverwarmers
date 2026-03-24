<x-layout title="Initiatieven" description="Blader door alle initiatieven op Hartverwarmers. Van muziek tot beweging, van natuur tot koken — ontdek activiteiten voor jouw woonzorgcentrum." :full-width="true">
    <div x-data="{
        search: new URLSearchParams(window.location.search).get('q') || '',
        selectedGoals: (new URLSearchParams(window.location.search).get('goals') || '').split(',').filter(Boolean),
        sortMode: new URLSearchParams(window.location.search).get('sort') || 'az',
        randomOrder: @js($randomOrder),
        goalLabels: @js(collect($goals)->pluck('keyword', 'tagSlug')->all()),
        initiatives: @js($initiatives->map(fn ($i) => [
            'id' => $i->id,
            'title' => $i->title,
            'fichesCount' => $i->fiches_count,
            'goalSlugs' => $i->tags->pluck('slug')->values(),
        ])),
        updateUrl() {
            const params = new URLSearchParams();
            if (this.sortMode && this.sortMode !== 'az') params.set('sort', this.sortMode);
            if (this.search) params.set('q', this.search);
            if (this.selectedGoals.length > 0) params.set('goals', this.selectedGoals.join(','));
            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState(null, '', url);
        },
        toggleGoal(tagSlug) {
            const idx = this.selectedGoals.indexOf(tagSlug);
            if (idx === -1) {
                this.selectedGoals.push(tagSlug);
            } else {
                this.selectedGoals.splice(idx, 1);
            }
        },
        removeGoal(tagSlug) {
            this.selectedGoals = this.selectedGoals.filter(s => s !== tagSlug);
        },
        clearAll() {
            this.selectedGoals = [];
            this.search = '';
            this.sortMode = 'az';
        },
        get headlineText() {
            const labels = this.selectedGoals.map(s => this.goalLabels[s]).filter(Boolean);
            if (labels.length === 0) return '';
            if (labels.length === 1) return `Initiatieven over ${labels[0]}`;
            const last = labels[labels.length - 1];
            return `Initiatieven die zowel ${labels.slice(0, -1).join(', ')} als ${last} zijn`;
        },
        isVisible(id) {
            const item = this.initiatives.find(i => i.id === id);
            if (!item) return false;
            if (this.search && !item.title.toLowerCase().includes(this.search.toLowerCase())) return false;
            if (this.selectedGoals.length > 0 && !this.selectedGoals.every(g => item.goalSlugs.includes(g))) return false;
            if (this.sortMode === 'rich' && item.fichesCount < 10) return false;
            if (this.sortMode === 'needs-love' && item.fichesCount >= 3) return false;
            return true;
        },
        get visibleCount() {
            return this.initiatives.filter(i => this.isVisible(i.id)).length;
        },
        get totalCount() {
            return this.initiatives.length;
        },
        get sortedIds() {
            if (this.sortMode === 'random') {
                return this.randomOrder;
            }
            const sorted = [...this.initiatives];
            if (this.sortMode === 'rich') {
                sorted.sort((a, b) => b.fichesCount - a.fichesCount);
            } else if (this.sortMode === 'needs-love') {
                sorted.sort((a, b) => a.fichesCount - b.fichesCount);
            } else {
                sorted.sort((a, b) => a.title.localeCompare(b.title, 'nl'));
            }
            return sorted.map(i => i.id);
        }
    }" x-init="$watch('search', () => updateUrl()); $watch('sortMode', () => updateUrl()); $watch('selectedGoals', () => updateUrl())">
        {{-- Zone 1: Hero (cream bg) --}}
        <section class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 pt-8 pb-8">
                <flux:breadcrumbs class="mb-6">
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Initiatieven</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <div class="max-w-3xl mb-8">
                    <span class="section-label section-label-hero">Initiatieven</span>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl mt-1">
                        <span x-show="selectedGoals.length === 0">Praktijkfiches van collega's, gebundeld per initiatief</span>
                        <span x-show="selectedGoals.length > 0" x-text="headlineText" x-cloak></span>
                    </h1>
                </div>

                {{-- Toolbar: Filter + Search + Sort --}}
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        {{-- DIAMANT goal filter dropdown --}}
                        @if(count($goals) > 0)
                            <flux:dropdown>
                                <button class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full font-semibold text-sm border border-[var(--color-border-light)] bg-white text-[var(--color-text-primary)] transition-all hover:bg-[var(--color-bg-cream)] hover:border-[var(--color-border-light)] cursor-pointer"
                                        :class="selectedGoals.length > 0 ? 'ring-2 ring-[var(--color-primary)] border-[var(--color-primary)]' : ''">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                        <polygon points="30,0 70,0 100,35 50,100 0,35" fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" />
                                        <line x1="0" y1="35" x2="100" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                        <line x1="30" y1="0" x2="50" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                        <line x1="70" y1="0" x2="50" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                        <line x1="25" y1="35" x2="50" y2="100" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                        <line x1="75" y1="35" x2="50" y2="100" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                    </svg>
                                    <span>Doelen</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                                <flux:popover class="w-96 !p-0">
                                    <div class="px-4 pt-3 pb-1">
                                        <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Wat wil je bereiken?</span>
                                    </div>
                                    <div class="divide-y divide-[var(--color-border-light)]">
                                        @foreach($goals as $goal)
                                            <label class="flex items-center gap-3 w-full px-4 py-3 cursor-pointer hover:bg-[var(--color-bg-cream)] transition-colors"
                                                   @click.stop>
                                                <input type="checkbox"
                                                       value="{{ $goal['tagSlug'] }}"
                                                       class="accent-[var(--color-primary)] w-4 h-4 rounded shrink-0"
                                                       :checked="selectedGoals.includes('{{ $goal['tagSlug'] }}')"
                                                       @change="toggleGoal('{{ $goal['tagSlug'] }}')">
                                                <div class="flex-1 min-w-0">
                                                    <span class="font-semibold text-sm text-[var(--color-text-primary)]">{{ $goal['keyword'] }}</span>
                                                    <p class="text-xs text-[var(--color-text-secondary)]">{{ $goal['description'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </flux:popover>
                            </flux:dropdown>
                        @endif

                        {{-- Search input --}}
                        <flux:input icon="magnifying-glass" placeholder="Zoek..." x-model.debounce.200ms="search" class="sm:max-w-48" />

                        {{-- Sort pills --}}
                        <div class="flex overflow-x-auto rounded-full bg-[var(--color-bg-subtle)] p-1 sm:ml-auto shrink-0">
                            <flux:tooltip content="Alle initiatieven op alfabetische volgorde" position="bottom">
                                <button
                                    @click="sortMode = 'az'"
                                    :class="sortMode === 'az' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer whitespace-nowrap"
                                    type="button"
                                >
                                    <flux:icon name="bars-3-bottom-left" class="size-4" />
                                    A&ndash;Z
                                </button>
                            </flux:tooltip>
                            <flux:tooltip content="Initiatieven met de meeste uitwerkingen" position="bottom">
                                <button
                                    @click="sortMode = 'rich'"
                                    :class="sortMode === 'rich' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all whitespace-nowrap cursor-pointer"
                                    type="button"
                                >
                                    <flux:icon name="square-3-stack-3d" class="size-4" />
                                    Veel fiches
                                </button>
                            </flux:tooltip>
                            <flux:tooltip content="Initiatieven die nog uitwerkingen zoeken" position="bottom">
                                <button
                                    @click="sortMode = 'needs-love'"
                                    :class="sortMode === 'needs-love' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all whitespace-nowrap cursor-pointer"
                                    type="button"
                                >
                                    <flux:icon name="hand-raised" class="size-4" />
                                    Hulp nodig
                                </button>
                            </flux:tooltip>
                            <flux:tooltip content="Willekeurige volgorde — verras jezelf!" position="bottom">
                                <button
                                    @click="sortMode = 'random'"
                                    :class="sortMode === 'random' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer whitespace-nowrap"
                                    type="button"
                                >
                                    <flux:icon name="arrows-right-left" class="size-4" />
                                    Willekeurig
                                </button>
                            </flux:tooltip>
                        </div>
                    </div>

                    {{-- Active filter pills --}}
                    @if(count($goals) > 0)
                        <div class="flex flex-wrap items-center gap-2" x-show="selectedGoals.length > 0" x-cloak>
                            @foreach($goals as $goal)
                                <template x-if="selectedGoals.includes('{{ $goal['tagSlug'] }}')">
                                    <flux:badge variant="pill" class="gap-1.5">
                                        <x-diamant-gem :letter="$goal['letter']" size="xxs" />
                                        {{ $goal['keyword'] }}
                                        <flux:badge.close @click="removeGoal('{{ $goal['tagSlug'] }}')" />
                                    </flux:badge>
                                </template>
                            @endforeach

                            <template x-if="selectedGoals.length > 1">
                                <button @click="selectedGoals = []" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] underline underline-offset-2 ml-1">
                                    Alles wissen
                                </button>
                            </template>
                        </div>
                    @endif
                </div>


            </div>
        </section>

        {{-- Grid section --}}
        <section>
            <div class="max-w-6xl mx-auto px-6 pt-10 pb-16">

                {{-- Initiatives grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $eagerIds = $initiatives->take(6)->pluck('id')->all(); @endphp
                    @foreach($initiatives as $initiative)
                        <div
                            x-show="isVisible({{ $initiative->id }})"
                            :style="'order: ' + sortedIds.indexOf({{ $initiative->id }})"
                            x-cloak
                        >
                            <x-initiative-card :initiative="$initiative" :show-fiche-count="true" :show-new-badge="true" :eager="in_array($initiative->id, $eagerIds)" />
                        </div>
                    @endforeach

                    {{-- "Jouw ervaring telt" callout (single-column card slot) --}}
                    @if(count($needsLoveInitiatives) > 0)
                        <div :style="'order: 999'">
                            <div class="relative rounded-[var(--radius-sm)] overflow-hidden flex flex-col justify-between p-6 aspect-[16/10]" style="background-color: var(--color-primary);">
                                {{-- Decorative large heart outline --}}
                                <svg class="absolute -bottom-6 -right-6 w-40 h-40 opacity-10" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                </svg>

                                <div class="relative z-10">
                                    <p class="font-heading font-bold text-2xl text-white leading-tight">Jouw ervaring telt!</p>
                                    <p class="text-white/85 mt-3 text-sm leading-snug">
                                        Initiatieven als
                                        @foreach($needsLoveInitiatives as $idx => $item)
                                            <a href="{{ $item['route'] }}" class="text-white font-semibold underline underline-offset-2 hover:text-white/70 transition-colors">{{ $item['title'] }}</a>@if($idx < count($needsLoveInitiatives) - 1), @endif
                                        @endforeach
                                        hebben nog weinig fiches. Deel jouw aanpak en help collega's op weg.
                                    </p>
                                </div>

                                <a href="{{ route('fiches.create') }}" class="relative z-10 mt-4 inline-flex items-center gap-1.5 text-white font-semibold text-sm hover:text-white/80 transition-colors self-end">
                                    Schrijf een fiche
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Empty state --}}
                <div x-show="visibleCount === 0" x-cloak class="text-center py-16">
                    <flux:icon.magnifying-glass class="mx-auto mb-4 size-12 text-[var(--color-border-light)]" variant="outline" />
                    <p class="text-[var(--color-text-secondary)] mb-4">Geen initiatieven gevonden met deze filters.</p>
                    <flux:button variant="outline" size="sm" @click="clearAll()">Alle filters wissen</flux:button>
                </div>
            </div>
        </section>
    </div>
</x-layout>
