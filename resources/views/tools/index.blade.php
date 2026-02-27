<x-layout title="Gidsen en tools" :full-width="true">
    {{-- Hero / Intro --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Gidsen en tools</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <span class="section-label section-label-hero">Hulpmiddelen relatiegerichte zorg</span>
            <h1 class="text-5xl mt-1">Gidsen en tools voor woonzorgteams</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Ontdek kosteloze gidsen en tools die woonzorgteams ondersteunen bij het bieden van de best mogelijke zorg en activiteiten voor ouderen.</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Gidsen --}}
    <section class="bg-[var(--color-bg-base)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Gidsen</span>
            <h2 class="mb-6" id="gidsen">Ontdek bewezen aanpakken voor je team</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('content.roadmap') }}" class="block cursor-pointer">
                    <flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
                        <div class="-mx-6 -mt-6 mb-4">
                            <img src="/img/content/gids-boekcover-roadmap.jpg" alt="Roadmap boekcover" class="w-full aspect-[16/10] object-cover">
                        </div>
                        <flux:heading size="lg" class="font-heading font-bold">Roadmap verandertraject wonen en leven</flux:heading>
                        <flux:text class="mt-2 line-clamp-3">Een uitgebreid stappenplan om je hele woonzorgteam mee te nemen in wonen en leven. Het biedt een concreet antwoord op de vernieuwde focus op welbevinden in het woonzorgdecreet.</flux:text>
                        <div class="mt-4 pt-3 border-t border-[var(--color-border-light)]">
                            <span class="cta-link text-sm">Lees meer</span>
                        </div>
                    </flux:card>
                </a>

                <a href="{{ route('content', 'wonen-en-leven') }}" class="block cursor-pointer">
                    <flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
                        <div class="-mx-6 -mt-6 mb-4">
                            <img src="/img/content/gids-boekcover-samenvattingen.jpg" alt="Samenvattingen boekcover" class="w-full aspect-[16/10] object-cover">
                        </div>
                        <flux:heading size="lg" class="font-heading font-bold">Overzicht van aanpakken rond wonen en leven</flux:heading>
                        <flux:text class="mt-2 line-clamp-3">Vergelijking en samenvattingen van courante aanpakken rond wonen en leven: BAM, Sense of home, Tubbe,..</flux:text>
                        <div class="mt-4 pt-3 border-t border-[var(--color-border-light)]">
                            <span class="cta-link text-sm">Lees meer</span>
                        </div>
                    </flux:card>
                </a>
            </div>
        </div>
    </section>

    {{-- Tools --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Tools</span>
            <h2 class="mb-6" id="tools">Materiaal om direct mee aan de slag te gaan</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($tools as $tool)
                    <a href="{{ route('tools.show', ['uid' => $tool['uid']]) }}" class="block cursor-pointer">
                        <flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
                            <div class="-mx-6 -mt-6 mb-4">
                                <img src="{{ $tool['preview_image'] ?? $tool['hero_image'] }}" alt="{{ $tool['title'] }}" class="w-full aspect-[16/10] object-cover">
                            </div>
                            <flux:heading size="lg" class="font-heading font-bold">{{ $tool['title'] }}</flux:heading>
                            @isset($tool['teaser'])
                                <flux:text class="mt-2 line-clamp-2">{{ Str::limit($tool['teaser'], 100) }}</flux:text>
                            @endisset
                            @isset($tool['format'])
                                <div class="mt-2">
                                    <flux:badge size="sm" color="zinc">{{ $tool['format'] }}</flux:badge>
                                </div>
                            @endisset
                            <div class="mt-4 pt-3 border-t border-[var(--color-border-light)]">
                                <span class="cta-link text-sm">Bekijk</span>
                            </div>
                        </flux:card>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
