<x-layout title="Initiatieven" description="Blader door alle initiatieven op Hartverwarmers. Van muziek tot beweging, van natuur tot koken — ontdek activiteiten voor jouw woonzorgcentrum." :full-width="true">
    <div x-data="{
        search: new URLSearchParams(window.location.search).get('q') || '',
        selectedGoals: (new URLSearchParams(window.location.search).get('goals') || '').split(',').filter(Boolean),
        sortMode: new URLSearchParams(window.location.search).get('sort') || 'az',
        randomOrder: @js($randomOrder),
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
        <section class="bg-[var(--color-bg-cream)]">
            <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
                <flux:breadcrumbs class="mb-6">
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Initiatieven</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <div class="max-w-3xl">
                    <span class="section-label section-label-hero">Initiatieven</span>
                    <h1 class="text-5xl mt-1">Van idee tot activiteit</h1>
                    <p class="text-xl text-[var(--color-text-secondary)] mt-4 font-light leading-relaxed">
                        Elk initiatief bundelt praktijkfiches van collega's. Kies een thema en ontdek hoe anderen het aanpakken.
                    </p>
                </div>

            </div>
        </section>

        <hr class="border-[var(--color-border-light)]">

        {{-- Zone 2: Recent activity with inline initiative switcher --}}
        @if($recentByInitiative->isNotEmpty())
            <section x-data="{
                selected: '{{ $recentByInitiative->keys()->first() }}',
                headingOpen: false,
                hoverTimeout: null,
                data: @js($recentByInitiative),
                openHeading() {
                    clearTimeout(this.hoverTimeout);
                    this.headingOpen = true;
                },
                closeHeading() {
                    this.hoverTimeout = setTimeout(() => this.headingOpen = false, 150);
                },
                get currentTitle() {
                    return this.data[this.selected]?.title ?? '';
                },
                get currentFiches() {
                    return this.data[this.selected]?.fiches ?? [];
                }
            }">
                <div class="max-w-6xl mx-auto px-6 py-16">
                    <span class="section-label">Recent gedeeld</span>
                    <h2 class="text-3xl mt-1 mb-8">
                        Collega's deelden over <span class="relative inline" @mouseenter="openHeading()" @mouseleave="closeHeading()">
                            <span class="italic cursor-pointer transition-colors hover:text-[var(--color-primary)] border-b border-dotted border-[var(--color-border-light)]"
                                  x-text="currentTitle"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 -mt-0.5 ml-0.5 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>

                            <div x-cloak x-show="headingOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 top-full pt-2 z-50">
                                <div class="bg-white rounded-lg shadow-lg border border-[var(--color-border-light)] py-1 whitespace-nowrap">
                                    @foreach($recentByInitiative as $slug => $item)
                                        <button class="block w-full px-4 py-2 text-left text-base font-heading font-bold hover:bg-[var(--color-bg-cream)] hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                                :class="selected === '{{ $slug }}' ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-primary)]'"
                                                @click="selected = '{{ $slug }}'; headingOpen = false">
                                            {{ $item['title'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="fiche in currentFiches" :key="fiche.id">
                            <a :href="fiche.url" class="flex items-start gap-3 p-3 rounded-xl hover:bg-[var(--color-bg-cream)] transition-colors group">
                                <template x-if="fiche.user_avatar">
                                    <img :src="fiche.user_avatar" :alt="fiche.user_name" class="w-12 h-12 rounded-full object-cover shrink-0" loading="lazy">
                                </template>
                                <template x-if="!fiche.user_avatar">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-xs shrink-0"
                                         :style="'background-color: ' + fiche.user_color_bg + '; color: ' + fiche.user_color_text"
                                         x-text="fiche.user_initial"></div>
                                </template>
                                <div class="min-w-0">
                                    <p class="font-semibold text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-primary)] transition-colors" x-text="fiche.title"></p>
                                    <p class="text-sm text-[var(--color-text-secondary)] mt-0.5"><span x-text="fiche.user_name"></span></p>
                                    <p class="text-xs text-[var(--color-text-secondary)] opacity-60 mt-0.5" x-text="fiche.time_ago"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </section>

            <hr class="border-[var(--color-border-light)] max-w-6xl mx-auto">
        @endif

        {{-- Zone 3: Grid section --}}
        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">

                <span class="section-label">Alle initiatieven</span>
                <h2 class="text-3xl mt-1 mb-8">Blader door het aanbod</h2>

                {{-- Toolbar: Filter + Search + Sort --}}
                <div class="space-y-4 mb-8">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        {{-- DIAMANT goal filter dropdown --}}
                        @if(count($goals) > 0)
                            <flux:dropdown>
                                <button class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full font-semibold text-sm border border-[var(--color-border-light)] bg-white text-[var(--color-text-primary)] transition-all hover:bg-[var(--color-bg-cream)] hover:border-[var(--color-border-light)] cursor-pointer"
                                        :class="selectedGoals.length > 0 ? 'ring-2 ring-[var(--color-primary)] border-[var(--color-primary)]' : ''">
                                    <x-diamant-gem size="xxs" />
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
                                                <x-diamant-gem :letter="$goal['letter']" size="xs" />
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
                        <div class="inline-flex rounded-full bg-[var(--color-bg-subtle)] p-1 sm:ml-auto flex-wrap">
                            <flux:tooltip content="Alle initiatieven op alfabetische volgorde" position="bottom">
                                <button
                                    @click="sortMode = 'az'"
                                    :class="sortMode === 'az' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer"
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
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer"
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

                {{-- Active sort clarification --}}
                <p class="text-sm text-[var(--color-text-secondary)] -mt-4 mb-6" x-cloak>
                    <span x-show="sortMode === 'az'">Alle initiatieven op alfabetische volgorde</span>
                    <span x-show="sortMode === 'rich'">Initiatieven met de meeste uitwerkingen</span>
                    <span x-show="sortMode === 'needs-love'">Initiatieven die nog uitwerkingen zoeken</span>
                    <span x-show="sortMode === 'random'">Willekeurige volgorde</span>
                </p>

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
                    <flux:icon.magnifying-glass class="mx-auto mb-4 text-[var(--color-border-light)]" variant="outline" />
                    <p class="text-[var(--color-text-secondary)] mb-4">Geen initiatieven gevonden met deze filters.</p>
                    <flux:button variant="outline" size="sm" @click="clearAll()">Alle filters wissen</flux:button>
                </div>
            </div>
        </section>
    </div>
</x-layout>
