<x-layout title="Diamantjes" description="Fiches die ons team uitkoos als bijzonder mooie voorbeelden van wat mogelijk is.">

    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)] overflow-hidden">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center">
            {{-- Copy --}}
            <div class="flex-1 px-6 py-16 md:py-20">
                <span class="section-label section-label-hero">Uitgelicht door ons team</span>
                <h1 class="text-5xl mt-2">Diamantjes</h1>
                <p class="text-[var(--color-text-secondary)] text-xl font-light mt-4 max-w-xl">
                    Af en toe stoot ons team op een fiche die we gewoon te goed vinden om onopgemerkt te laten. Dit zijn ze.
                </p>
                <p class="mt-3 text-sm text-[var(--color-text-secondary)]">
                    {{ $fiches->count() }} {{ $fiches->count() === 1 ? 'fiche' : 'fiches' }} uitgekozen door het team
                </p>
            </div>

            {{-- Hero visual --}}
            <div class="flex-1 hidden md:flex items-center justify-center relative overflow-hidden min-h-64">
                <img
                    src="{{ asset('images/hero-diamantjes.webp') }}"
                    alt="Een collage van uitgelichte activiteitsfiches — de diamantjes van Hartverwarmers"
                    class="w-full h-full object-cover"
                    width="1024"
                    height="1024"
                    loading="eager"
                />
                {{-- Decorative diamonds --}}
                <x-diamant-gem size="lg" :pronounced="true" class="absolute top-8 right-10 opacity-80" />
                <x-diamant-gem size="md" :pronounced="false" class="absolute top-4 right-28 opacity-40" />
                <x-diamant-gem size="sm" :pronounced="false" class="absolute bottom-12 right-6 opacity-50" />
                <x-diamant-gem size="xs" :pronounced="false" class="absolute top-16 right-44 opacity-30" />
            </div>
        </div>
    </section>

    {{-- Body: main content + sidebar --}}
    <section class="bg-[var(--color-bg-base)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                {{-- Main content --}}
                <div class="lg:col-span-2">
                    @if($fiches->isEmpty())
                        <div class="rounded-[var(--radius-sm)] border border-dashed border-[var(--color-border-light)] py-16 text-center">
                            <p class="text-[var(--color-text-secondary)]">Er zijn nog geen diamantjes geselecteerd.</p>
                        </div>
                    @else
                        {{-- Featured card --}}
                        @php $featured = $fiches->first(); @endphp
                        <a href="{{ route('fiches.show', [$featured->initiative, $featured]) }}"
                           data-featured-card
                           class="block mb-6 no-underline text-inherit">
                            <flux:card class="!p-0 overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
                                <div class="p-6">
                                    <flux:heading size="xl" class="font-heading font-bold">{{ $featured->title }}</flux:heading>

                                    @if($featured->description)
                                        <flux:text class="mt-2 text-base leading-relaxed">
                                            {{ strip_tags($featured->description) }}
                                        </flux:text>
                                    @endif

                                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-[var(--color-border-light)] text-sm text-[var(--color-text-secondary)]">
                                        @if($featured->user)
                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                <x-user-avatar :user="$featured->user" size="xs" />
                                                <span class="truncate">{{ $featured->user->full_name }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-3 shrink-0">
                                            @if($featured->kudos_count > 0)
                                                <span class="flex items-center gap-1">
                                                    <x-icon-heart class="w-4 h-4" />
                                                    {{ $featured->kudos_count }}
                                                </span>
                                            @endif
                                            @if(($featured->comments_count ?? 0) > 0)
                                                <span class="flex items-center gap-1">
                                                    <x-icon-comment class="w-4 h-4" />
                                                    {{ $featured->comments_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </flux:card>
                        </a>

                        {{-- 2-col grid for remaining fiches --}}
                        @if($fiches->count() > 1)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                @foreach($fiches->skip(1) as $fiche)
                                    <x-fiche-card :fiche="$fiche" :hideDiamond="true" />
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-8 space-y-6">
                        {{-- Hoe kiezen we? --}}
                        <div class="rounded-xl bg-white border border-[var(--color-border-light)] overflow-hidden">
                            <div class="bg-[var(--color-bg-cream)] px-5 py-4 border-b border-[var(--color-border-light)]">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)]/10 flex items-center justify-center shrink-0">
                                        <x-diamant-gem size="xxs" :pronounced="true" />
                                    </div>
                                    <h3 class="font-heading font-bold">Hoe kiezen we?</h3>
                                </div>
                            </div>
                            <div class="px-5 py-4 space-y-3">
                                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                                    Het Hartverwarmers-team — Frederik Vincx en Maite Mallentjer — kiest fiches die bijzonder inspireren.
                                </p>
                                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                                    We zoeken naar activiteiten die echte <em>diamantjes zijn</em>: fiches die de DIAMANT-principes op een bijzondere manier tot leven brengen en collega's écht verder helpen.
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
