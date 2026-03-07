<x-layout :title="$facet['keyword'] . ' — DIAMANT-kompas'" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('goals.index') }}">DIAMANT-kompas</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $facet['keyword'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div>
                <span class="section-label section-label-hero">Doelstelling</span>
                <h1 class="text-5xl mt-1 mb-6">{{ $facet['keyword'] }}</h1>

                <div class="text-2xl leading-relaxed font-light text-[var(--color-text-secondary)] lg:w-1/2">
                    <p>{{ Str::before($facet['description'], '. ') . '.' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Question + Reframe + Checklist --}}
    <section class="overflow-visible">
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start overflow-visible">
                {{-- Left: Question, reframe --}}
                <div>
                    <span class="section-label">Vraag je af</span>
                    <h2 class="mt-1 mb-6">{{ $facet['core_question'] }}</h2>

                    @if(!empty($facet['quote']))
                        @php
                            $parts = explode('Maar:', $facet['quote']);
                            $niet = trim(str_replace('Niet:', '', $parts[0] ?? ''), ' .');
                            $maar = trim($parts[1] ?? '');
                        @endphp
                        <div class="space-y-3">
                            <div class="flex items-baseline gap-3">
                                <span class="w-14 shrink-0 inline-block bg-zinc-100 text-zinc-600 text-sm font-semibold px-2.5 py-0.5 rounded text-center">NIET</span>
                                <p class="text-xl font-light text-[var(--color-text-secondary)] line-through">{{ ucfirst($niet) }}</p>
                            </div>
                            <div class="flex items-baseline gap-3">
                                <span class="w-14 shrink-0 inline-block bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] text-sm font-semibold px-2.5 py-0.5 rounded text-center">WEL</span>
                                <p class="text-xl font-light text-[var(--color-text-primary)]">{{ ucfirst($maar) }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right: Paper checklist (pulled up into hero) --}}
                @if(!empty($facet['reflection_questions']))
                    <div class="hidden lg:block lg:-translate-y-[30%] px-8">
                        <div class="quote-paper quote-paper-lg">
                            <span class="checklist-label">Checklist</span>
                            @foreach($facet['reflection_questions'] as $question)
                                <div class="checklist-item">
                                    <span class="question-badge">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <polygon points="30,0 70,0 100,35 50,100 0,35" fill="none" stroke="var(--color-primary)" stroke-width="8" stroke-linejoin="round" />
                                            <line x1="0" y1="35" x2="100" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="30" y1="0" x2="50" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="70" y1="0" x2="50" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="25" y1="35" x2="50" y2="100" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="75" y1="35" x2="50" y2="100" stroke="var(--color-primary)" stroke-width="4" />
                                        </svg>
                                    </span>
                                    <p class="font-body font-light">{{ $question }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mobile: inline --}}
                    <div class="lg:hidden px-8">
                        <div class="quote-paper quote-paper-lg">
                            <span class="checklist-label">Checklist</span>
                            @foreach($facet['reflection_questions'] as $question)
                                <div class="checklist-item">
                                    <span class="question-badge">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <polygon points="30,0 70,0 100,35 50,100 0,35" fill="none" stroke="var(--color-primary)" stroke-width="8" stroke-linejoin="round" />
                                            <line x1="0" y1="35" x2="100" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="30" y1="0" x2="50" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="70" y1="0" x2="50" y2="35" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="25" y1="35" x2="50" y2="100" stroke="var(--color-primary)" stroke-width="4" />
                                            <line x1="75" y1="35" x2="50" y2="100" stroke="var(--color-primary)" stroke-width="4" />
                                        </svg>
                                    </span>
                                    <p class="font-body font-light">{{ $question }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Practice Examples (zigzag) — hidden until images are ready --}}
    @if(false && !empty($facet['practice_examples']))
        <section class="bg-[var(--color-bg-cream)]">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="text-center mb-12">
                    <span class="section-label">In de praktijk</span>
                    <h2 class="mt-1 mb-2">Zo herken je het</h2>
                    @if(!empty($facet['practice_subtitle']))
                        <p class="text-[var(--color-text-secondary)]">{{ $facet['practice_subtitle'] }}</p>
                    @endif
                </div>

                <div class="space-y-12">
                    @foreach(array_slice($facet['practice_examples'], 0, 2) as $i => $example)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                            {{-- Image --}}
                            <div class="{{ $i % 2 === 1 ? 'md:order-2' : '' }}">
                                @if(!empty($example['image']))
                                    <div class="bg-white rounded-xl overflow-hidden aspect-4/3 flex items-center justify-center">
                                        <img src="{{ $example['image'] }}" alt="{{ $example['name'] }}" class="w-full h-full object-contain">
                                    </div>
                                @else
                                    <div class="bg-[var(--color-bg-subtle)] rounded-xl aspect-4/3 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Text --}}
                            <div class="{{ $i % 2 === 1 ? 'md:order-1' : '' }}">
                                <h3 class="mb-2">{{ $example['role'] }}</h3>
                                <p class="text-lg text-[var(--color-text-secondary)] font-light">{{ $example['story'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Block 5: Initiatieven --}}
    @if(!$initiatives->isEmpty())
        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <span class="section-label">Inspiratie</span>
                <h2 class="mt-1 mb-2">{{ $facet['initiatives_heading'] ?? 'Initiatieven bij deze doelstelling' }}</h2>
                <p class="text-[var(--color-text-secondary)] mb-8">Gebruik deze als startpunt en pas ze aan voor jouw bewoners.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives as $initiative)
                        <x-initiative-card :initiative="$initiative" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Verwante doelstellingen --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">DIAMANT-kompas</span>
            <h2 class="mt-1 mb-6">Verwante doelstellingen</h2>
            @if(!empty($facet['related_facets_text']))
                <p class="text-lg text-[var(--color-text-secondary)] max-w-3xl mb-8">{!! $facet['related_facets_text'] !!}</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($facet['related_facets'] ?? [] as $relatedSlug)
                    @if(isset($allFacets[$relatedSlug]))
                        @php $item = $allFacets[$relatedSlug]; @endphp
                        <a href="{{ route('goals.show', $relatedSlug) }}"
                           class="flex items-center gap-4 p-4 rounded-xl hover:bg-[var(--color-bg-subtle)] transition-colors">
                            <x-diamant-gem :letter="$item['letter']" size="md" />
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-[var(--color-text-primary)]">{{ $item['keyword'] }}</span>
                                <span class="text-[var(--color-text-secondary)]"> &middot; {{ $item['tagline'] }}</span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
