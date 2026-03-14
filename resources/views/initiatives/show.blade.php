<x-layout :title="$initiative->title" :description="$initiative->description ? Str::limit(strip_tags($initiative->description), 160) : 'Bekijk het initiatief ' . $initiative->title . ' op Hartverwarmers.'" :full-width="true">
    @auth
        @if(auth()->user()->isAdmin())
            <flux:modal name="delete-initiative" class="max-w-md">
                <div class="space-y-4">
                    <flux:heading size="lg">Initiatief verwijderen?</flux:heading>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Weet je zeker dat je <strong>{{ $initiative->title }}</strong> wilt verwijderen? Dit initiatief en alle bijbehorende fiches worden verborgen.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuleren</flux:button>
                        </flux:modal.close>
                        <form action="{{ route('initiatives.destroy', $initiative) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger">Verwijderen</flux:button>
                        </form>
                    </div>
                </div>
            </flux:modal>
        @endif
    @endauth

    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-6 lg:pb-8">
            <div class="flex items-center justify-between mb-6">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Alle initiatieven</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $initiative->title }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                @auth
                    @if(auth()->user()->isAdmin())
                        <flux:modal.trigger name="delete-initiative">
                            <flux:button variant="danger" size="sm" icon="trash">Verwijderen</flux:button>
                        </flux:modal.trigger>
                    @endif
                @endauth
            </div>

            <span class="section-label section-label-hero">Initiatief</span>

            <h1 class="text-5xl sm:text-6xl mt-1 mb-4">{{ $initiative->title }}</h1>

            @if($initiative->description)
                <div class="text-[var(--color-text-secondary)] text-2xl font-light mb-8 max-w-3xl">
                    {!! $initiative->description !!}
                </div>
            @endif

            @if($initiative->content)
                <div class="prose prose-lg max-w-3xl mb-6">
                    {!! $initiative->content !!}
                </div>
            @endif

            @php
                $nonGoalTags = $initiative->tags->filter(fn ($tag) => $tag->type !== 'goal');
            @endphp
            @if($nonGoalTags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-6">
                    @foreach($nonGoalTags as $tag)
                        <flux:badge variant="outline">{{ $tag->name }}</flux:badge>
                    @endforeach
                </div>
            @endif

            @if($initiative->comments->isNotEmpty())
                <div class="meta-group">
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        {{ $initiative->comments->count() }} keer {{ $initiative->comments->count() === 1 ? 'ervaring' : 'ervaringen' }} gedeeld
                    </span>
                </div>
            @endif
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Main content: two-column layout --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                {{-- Left column: Fiches --}}
                <div class="lg:col-span-2" x-data="{
                    search: new URLSearchParams(window.location.search).get('q') || '',
                    sortMode: new URLSearchParams(window.location.search).get('sort') || 'newest',
                    fiches: @js($ficheAlpineData),
                    randomOrder: @js($randomOrder),
                    updateUrl() {
                        const params = new URLSearchParams();
                        if (this.sortMode && this.sortMode !== 'newest') params.set('sort', this.sortMode);
                        if (this.search) params.set('q', this.search);
                        const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                        history.replaceState(null, '', url);
                    },
                    isVisible(id) {
                        if (!this.search) return true;
                        const item = this.fiches.find(f => f.id === id);
                        if (!item) return false;
                        const q = this.search.toLowerCase();
                        return item.title.toLowerCase().includes(q) || item.description.toLowerCase().includes(q);
                    },
                    get sortedIds() {
                        const visible = this.fiches.filter(f => this.isVisible(f.id));
                        if (this.sortMode === 'random') {
                            return this.randomOrder.filter(id => visible.some(f => f.id === id));
                        }
                        const sorted = [...visible];
                        if (this.sortMode === 'popular') {
                            sorted.sort((a, b) => b.kudosCount - a.kudosCount);
                        } else if (this.sortMode === 'az') {
                            sorted.sort((a, b) => a.title.localeCompare(b.title, 'nl'));
                        } else {
                            sorted.sort((a, b) => b.createdAt - a.createdAt);
                        }
                        return sorted.map(f => f.id);
                    },
                    get visibleCount() {
                        return this.fiches.filter(f => this.isVisible(f.id)).length;
                    }
                }" x-init="$watch('search', () => updateUrl()); $watch('sortMode', () => updateUrl())">
                    <span class="section-label">Fiches</span>

                    @if($initiative->fiches->isEmpty())
                        <h2 class="mt-1 mb-8">Fiches door collega's</h2>
                        <p class="text-[var(--color-text-secondary)]">Nog geen fiches voor dit initiatief.</p>
                    @else
                        <h2 class="mt-1 mb-8">{{ $initiative->fiches->count() }} {{ $initiative->fiches->count() === 1 ? 'uitwerking' : 'uitwerkingen' }} door collega's</h2>

                        {{-- Toolbar: Search + Sort --}}
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-6">
                            <flux:input icon="magnifying-glass" placeholder="Zoek..." x-model.debounce.200ms="search" class="sm:max-w-48" />

                            <div class="inline-flex rounded-full bg-[var(--color-bg-subtle)] p-1 sm:ml-auto flex-wrap">
                                <flux:tooltip content="Nieuwste fiches eerst" position="bottom">
                                    <button
                                        @click="sortMode = 'newest'"
                                        :class="sortMode === 'newest' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer"
                                        type="button"
                                    >
                                        <flux:icon name="clock" class="size-4" />
                                        Nieuwste
                                    </button>
                                </flux:tooltip>
                                <flux:tooltip content="Fiches met de meeste kudos" position="bottom">
                                    <button
                                        @click="sortMode = 'popular'"
                                        :class="sortMode === 'popular' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all cursor-pointer"
                                        type="button"
                                    >
                                        <flux:icon name="heart" class="size-4" />
                                        Populair
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
                                <flux:tooltip content="Alfabetische volgorde" position="bottom">
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
                            </div>
                        </div>

                        {{-- Active sort clarification --}}
                        <p class="text-sm text-[var(--color-text-secondary)] mb-4" x-cloak>
                            <span x-show="sortMode === 'newest'">Nieuwste fiches eerst</span>
                            <span x-show="sortMode === 'popular'">Fiches met de meeste kudos</span>
                            <span x-show="sortMode === 'random'">Willekeurige volgorde</span>
                            <span x-show="sortMode === 'az'">Alfabetische volgorde</span>
                        </p>

                        {{-- Fiche list (flex container required for CSS order to work) --}}
                        <div class="flex flex-col gap-2">
                            @foreach($initiative->fiches as $fiche)
                                <a
                                    href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
                                    class="fiche-list-item"
                                    x-show="isVisible({{ $fiche->id }})"
                                    :style="'order: ' + sortedIds.indexOf({{ $fiche->id }})"
                                    x-cloak
                                >
                                    <span class="fiche-list-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </span>
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <span class="font-body font-semibold text-base text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">
                                            {{ $fiche->user?->full_name }}@if($fiche->user?->organisation), {{ $fiche->user->organisation }}@endif
                                        </span>
                                    </div>
                                    <span class="fiche-list-kudos {{ $fiche->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                        {{ $fiche->kudos_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        {{-- Empty search state --}}
                        <div x-show="visibleCount === 0" x-cloak class="text-center py-12">
                            <flux:icon.magnifying-glass class="mx-auto mb-4 text-[var(--color-border-light)]" variant="outline" />
                            <p class="text-[var(--color-text-secondary)] mb-4">Geen fiches gevonden.</p>
                            <flux:button variant="outline" size="sm" @click="search = ''">Wis zoekopdracht</flux:button>
                        </div>
                    @endif
                </div>

                {{-- Right column: DIAMANT analyse card --}}
                <div class="lg:col-span-1">
                    @feature('diamant-goals')
                    @if($initiative->image || $diamantAnalyse)
                        <div class="rounded-2xl bg-white shadow-lg lg:sticky lg:top-8 lg:self-start lg:-mt-[180px] overflow-hidden">
                            @if($initiative->image)
                                <img src="{{ $initiative->image }}" alt="{{ $initiative->title }}" class="w-full aspect-video object-cover" loading="lazy">
                            @endif

                            <div class="p-7">
                                <h3 class="text-[1.375rem] text-[var(--color-primary)]">Laat het initiatief schitteren</h3>

                                @if($diamantAnalyse)
                                    <p class="mt-2 mb-4 text-[1.0625rem] text-[var(--color-text-secondary)] leading-relaxed">
                                        Haal meer uit dit initiatief door DIAMANT-principes toe te passen.
                                    </p>

                                    <div x-data="{ open: {} }" class="space-y-3">
                                        @foreach($diamantAnalyse as $index => $item)
                                            <div class="diamant-accordion-item">
                                                <button
                                                    @click="open[{{ $index }}] = !open[{{ $index }}]"
                                                    class="flex items-center gap-2 w-full text-left py-1.5 font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors"
                                                >
                                                    {{-- Diamond gem icon --}}
                                                    <svg class="w-[18px] h-[18px] shrink-0 text-[var(--color-primary)]" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                                        <polygon points="30,0 70,0 100,35 50,100 0,35" fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" />
                                                        <line x1="0" y1="35" x2="100" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                                        <line x1="30" y1="0" x2="50" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                                        <line x1="70" y1="0" x2="50" y2="35" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                                        <line x1="25" y1="35" x2="50" y2="100" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                                        <line x1="75" y1="35" x2="50" y2="100" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                                                    </svg>

                                                    <span>{{ $item['keyword'] }}</span>

                                                    {{-- Chevron --}}
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="2"
                                                        stroke="currentColor"
                                                        class="w-3.5 h-3.5 ml-auto shrink-0 text-[var(--color-text-secondary)] transition-transform duration-200"
                                                        :class="open[{{ $index }}] ? 'rotate-180' : ''"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>

                                                <div x-show="open[{{ $index }}]" x-collapse x-cloak>
                                                    <div class="diamant-accordion-body">
                                                        {{ $item['text'] }}
                                                        <a href="{{ route('goals.show', $item['slug']) }}" class="cta-link text-[0.8125rem] mt-1.5">Bekijk doel</a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @endfeature
                </div>
            </div>
        </div>
    </section>

    {{-- Community Stories (hidden for now)
    <hr class="border-[var(--color-border-light)]">

    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                Uit de praktijk
            </span>
            <h2 class="mt-1 mb-4">Vertel, hoe ging het bij jullie?</h2>

            <!-- Existing comments -->
            @if($initiative->comments->isNotEmpty())
                @foreach($initiative->comments as $comment)
                    <div class="flex gap-4 py-4 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
                        <x-user-avatar :user="$comment->user" size="md" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="text-sm">
                                    <span class="font-semibold">{{ $comment->user->full_name ?? 'Anoniem' }}</span>
                                    @if($comment->user?->organisation)
                                        <span class="text-[var(--color-text-secondary)]"> &middot; {{ $comment->user->organisation }}</span>
                                    @endif
                                </div>
                                <span class="text-sm text-[var(--color-text-secondary)] shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2">{{ $comment->body }}</p>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Comment form -->
            @auth
                <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 {{ $initiative->comments->isNotEmpty() ? 'mt-8' : '' }}">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                            {{ substr(auth()->user()->first_name, 0, 1) }}
                        </div>
                        <span class="text-sm font-medium">{{ auth()->user()->full_name }}</span>
                    </div>
                    <form action="{{ route('initiatives.comment', $initiative) }}" method="POST">
                        @csrf
                        <textarea
                            name="body"
                            placeholder="Hoe ging het bij jullie? Wat viel op, wat werkte goed?"
                            rows="3"
                            required
                            class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-4 py-3 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <div class="mt-3 flex items-center {{ $initiative->comments->isEmpty() ? 'justify-between' : 'justify-end' }}">
                            @if($initiative->comments->isEmpty())
                                <span class="text-sm text-[var(--color-text-secondary)]">Wees de eerste die een ervaring deelt.</span>
                            @endif
                            <flux:button type="submit" variant="primary" size="sm">Deel je ervaring</flux:button>
                        </div>
                    </form>
                </div>
            @else
                @if(Route::has('login'))
                    <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8 text-center">
                        <a href="{{ route('login') }}" class="cta-link">Log in</a> om je ervaring te delen.
                    </div>
                @endif
            @endauth
        </div>
    </section>
    --}}

    {{-- Related Initiatives --}}
    @if($relatedInitiatives->isNotEmpty())
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <span class="section-label">Meer inspiratie</span>
                <h2 class="mb-8">Gerelateerde initiatieven</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedInitiatives as $related)
                        <x-initiative-card :initiative="$related" />
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('initiatives.index') }}" class="cta-link">Alle initiatieven</a>
                </div>
            </div>
        </section>
    @endif
</x-layout>
