<x-layout title="Initiatieven" description="Blader door alle initiatieven op Hartverwarmers. Van muziek tot beweging, van natuur tot koken — ontdek activiteiten voor jouw woonzorgcentrum." :full-width="true">
    <div x-data="{
        search: '',
        selectedGoals: [],
        initiatives: @js($initiatives->map(fn ($i) => [
            'id' => $i->id,
            'title' => $i->title,
            'goalSlugs' => $i->tags->pluck('slug')->values(),
        ])),
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
        },
        isVisible(id) {
            const item = this.initiatives.find(i => i.id === id);
            if (!item) return false;
            if (this.search && !item.title.toLowerCase().includes(this.search.toLowerCase())) return false;
            if (this.selectedGoals.length > 0 && !this.selectedGoals.every(g => item.goalSlugs.includes(g))) return false;
            return true;
        },
        get visibleCount() {
            return this.initiatives.filter(i => this.isVisible(i.id)).length;
        },
        get totalCount() {
            return this.initiatives.length;
        }
    }">
        {{-- Hero with filters --}}
        <section class="bg-[var(--color-bg-cream)]">
            <div class="max-w-6xl mx-auto px-6 pt-8 pb-10">
                <flux:breadcrumbs class="mb-6">
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Initiatieven</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <span class="section-label section-label-hero">Initiatieven</span>
                <h1 class="text-5xl mt-1">Ontdek inspirerende activiteiten</h1>
                <p class="text-2xl text-[var(--color-text-secondary)] mt-4">
                    @if(count($goals) > 0)
                        Vind het juiste initiatief via de DIAMANT-doelen of zoek op naam.
                    @else
                        Vind het juiste initiatief of zoek op naam.
                    @endif
                </p>

                {{-- Filter controls --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-8">
                    @if(count($goals) > 0)
                    {{-- Goal filter dropdown --}}
                    <flux:dropdown>
                        <button class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full font-semibold text-sm text-white transition-all hover:shadow-md"
                                style="background-color: var(--color-primary);"
                                onmouseover="this.style.backgroundColor='var(--color-primary-hover)'"
                                onmouseout="this.style.backgroundColor='var(--color-primary)'">
                            <x-diamant-gem size="xxs" :inverted="true" />
                            <span>Doelen filteren</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                    <flux:input icon="magnifying-glass" placeholder="Zoek op naam..." x-model.debounce.200ms="search" class="sm:max-w-xs" />

                    {{-- Results counter --}}
                    <p class="text-sm text-[var(--color-text-secondary)] shrink-0 sm:ml-auto">
                        Toont <span class="font-semibold text-[var(--color-text-primary)]" x-text="visibleCount"></span>
                        van <span x-text="totalCount"></span> initiatieven
                    </p>
                </div>

                {{-- Active filter pills --}}
                @if(count($goals) > 0)
                <div class="flex flex-wrap items-center gap-2 mt-4" x-show="selectedGoals.length > 0" x-cloak>
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
        </section>

        <hr class="border-[var(--color-border-light)]">

        {{-- Content --}}
        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">

                {{-- Initiatives grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives as $initiative)
                        <div x-show="isVisible({{ $initiative->id }})" x-cloak>
                            <x-initiative-card :initiative="$initiative" variant="detailed" :show-fiche-count="true" />
                        </div>
                    @endforeach
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
