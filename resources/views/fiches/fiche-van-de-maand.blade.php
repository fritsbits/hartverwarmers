<x-layout title="Fiches van de maand" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Fiches van de maand</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Archief</span>
            <h1 class="text-5xl mt-1">Fiches van de maand</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] font-light mt-4 max-w-2xl">Elke maand zetten we een fiche in de kijker die bijzonder inspirerend is.</p>
        </div>
    </section>

    {{-- List --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                {{-- Left: cards --}}
                <div class="lg:col-span-2">
                    @if($fiches->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($fiches as $fiche)
                                @php
                                    $previews = $fiche->cardPreviewImages(3);
                                    $isCurrent = $fiche->featured_month === now()->format('Y-m');
                                @endphp
                                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="group flex items-stretch gap-0 rounded-[var(--radius-sm)] border border-[var(--color-border-light)] bg-white overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 hover:border-[var(--color-border-hover)] transition-all duration-200 no-underline text-inherit">
                                    {{-- Month label --}}
                                    <div class="shrink-0 aspect-square w-20 sm:w-24 flex flex-col items-center justify-center {{ $isCurrent ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-bg-cream)] text-[var(--color-text-secondary)]' }} text-center">
                                        <span class="text-xl font-heading font-bold leading-none capitalize">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $fiche->featured_month)->translatedFormat('M') }}
                                        </span>
                                        <span class="text-xs mt-0.5 {{ $isCurrent ? 'text-white/70' : 'opacity-60' }}">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $fiche->featured_month)->translatedFormat('Y') }}
                                        </span>
                                    </div>

                                    {{-- Preview thumbnail --}}
                                    @if(count($previews) > 0)
                                        <div class="hidden sm:flex shrink-0 aspect-square w-24 bg-[var(--color-bg-cream)] items-center justify-center relative overflow-hidden">
                                            @foreach($previews as $i => $url)
                                                @php
                                                    $rotations = ['-5deg', '0deg', '4deg'];
                                                    $offsets = ['-3px', '0px', '3px'];
                                                @endphp
                                                <div class="absolute" style="width: 55%; transform: translate({{ $offsets[$i] }}, {{ $offsets[$i] }}) rotate({{ $rotations[$i] }}); z-index: {{ $i + 1 }};">
                                                    <img src="{{ $url }}" alt="" loading="lazy" class="w-full bg-white" style="box-shadow: 4px 6px 16px rgba(120, 90, 60, 0.2);">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Content --}}
                                    <div class="flex-1 flex flex-col justify-center px-6 py-4 min-w-0">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 min-w-0">
                                                <span class="font-heading font-bold text-xl text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors">
                                                    {{ $fiche->title }}
                                                </span>
                                                @if($fiche->description)
                                                    <p class="text-base text-[var(--color-text-secondary)] mt-1 line-clamp-1">
                                                        {{ Str::limit(strip_tags($fiche->description), 120) }}
                                                    </p>
                                                @endif
                                            </div>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 text-[var(--color-text-secondary)]/30 group-hover:text-[var(--color-primary)] transition-colors hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        </div>

                                        <div class="flex items-center gap-4 mt-2.5 text-sm text-[var(--color-text-secondary)]">
                                            @if($fiche->user)
                                                <span class="flex items-center gap-1.5">
                                                    @if($fiche->user->avatar_path)
                                                        <img src="{{ $fiche->user->avatarUrl() }}" alt="" class="w-4 h-4 rounded-full object-cover">
                                                    @endif
                                                    {{ $fiche->user->full_name }}
                                                </span>
                                            @endif
                                            @if($fiche->initiative)
                                                <span>{{ $fiche->initiative->title }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-[var(--radius-sm)] border border-dashed border-[var(--color-border-light)] py-16 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto text-[var(--color-text-secondary)]/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                            <p class="text-[var(--color-text-secondary)]">Er zijn nog geen fiches van de maand geselecteerd.</p>
                        </div>
                    @endif
                </div>

                {{-- Right: infobox --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-8 space-y-6">
                        {{-- About box --}}
                        <div class="rounded-xl bg-white border border-[var(--color-border-light)] overflow-hidden">
                            <div class="bg-[var(--color-bg-cream)] px-5 py-4 border-b border-[var(--color-border-light)]">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)]/10 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                                    </div>
                                    <h3 class="font-heading font-bold">Hoe kiezen we?</h3>
                                </div>
                            </div>
                            <div class="px-5 py-4 space-y-3">
                                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                                    Het Hartverwarmers-team — Frederik Vincx en Maite Mallentjer — kiest elke maand een fiche die bijzonder inspireert.
                                </p>
                                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                                    We zoeken naar activiteiten die echte <em>diamantjes</em> zijn: fiches die de DIAMANT-principes op een bijzondere manier tot leven brengen en collega's écht verder helpen.
                                </p>
                            </div>
                        </div>

                        {{-- Links --}}
                        <div class="space-y-2">
                            @feature('diamant-goals')
                            <a href="{{ route('goals.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg border border-[var(--color-border-light)] bg-white hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200 no-underline text-inherit group">
                                <div class="w-8 h-8 rounded-full bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-semibold group-hover:text-[var(--color-primary)] transition-colors">Het DIAMANT-kompas</span>
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-0.5">De zeven doelen achter elke fiche</p>
                                </div>
                            </a>
                            @endfeature
                            @auth
                                <a href="{{ route('fiches.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg border border-[var(--color-border-light)] bg-white hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200 no-underline text-inherit group">
                                    <div class="w-8 h-8 rounded-full bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-semibold group-hover:text-[var(--color-primary)] transition-colors">Deel jouw fiche</span>
                                        <p class="text-xs text-[var(--color-text-secondary)] mt-0.5">Schrijf zelf een fiche en maak kans</p>
                                    </div>
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
