<x-layout title="Gidsen en tools">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">Gidsen en tools</li>
            </ol>
        </nav>

        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Hulpmiddelen relatiegerichte zorg</p>
        <h1 class="text-5xl mt-1">Gidsen en tools voor woonzorgteams</h1>
        <p class="text-2xl text-[var(--color-text-secondary)] mt-4 mb-10">Ontdek kosteloze gidsen en tools die woonzorgteams ondersteunen bij het bieden van de best mogelijke zorg en activiteiten voor ouderen.</p>

        {{-- Gidsen --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mt-10 mb-6" id="gidsen">Gidsen</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <flux:card class="flex flex-col h-full">
                <img src="/img/content/gids-boekcover-roadmap.jpg" alt="Roadmap boekcover" class="w-full h-48 object-cover rounded-t-lg">
                <div class="p-5 flex-1 flex flex-col">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Roadmap verandertraject wonen en leven</h3>
                    <p class="text-[var(--color-text-secondary)] mt-2 flex-1">Een uitgebreid stappenplan om je hele woonzorgteam mee te nemen in wonen en leven. Het biedt een concreet antwoord op de vernieuwde focus op welbevinden in het woonzorgdecreet.</p>
                    <a href="{{ route('content.roadmap') }}" class="mt-4 inline-flex items-center text-sm font-medium text-[var(--color-primary)] hover:underline">
                        Lees meer &rarr;
                    </a>
                </div>
            </flux:card>

            <flux:card class="flex flex-col h-full">
                <img src="/img/content/gids-boekcover-samenvattingen.jpg" alt="Samenvattingen boekcover" class="w-full h-48 object-cover rounded-t-lg">
                <div class="p-5 flex-1 flex flex-col">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Overzicht van aanpakken rond wonen en leven</h3>
                    <p class="text-[var(--color-text-secondary)] mt-2 flex-1">Vergelijking en samenvattingen van courante aanpakken rond wonen en leven: BAM, Sense of home, Tubbe,..</p>
                    <a href="{{ route('content', 'wonen-en-leven') }}" class="mt-4 inline-flex items-center text-sm font-medium text-[var(--color-primary)] hover:underline">
                        Lees meer &rarr;
                    </a>
                </div>
            </flux:card>
        </div>

        <hr class="my-10 border-[var(--color-border-light)]">

        {{-- Tools --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6" id="tools">Tools</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($tools as $tool)
                <flux:card class="flex flex-row overflow-hidden">
                    <div class="w-1/3 flex-shrink-0">
                        <a href="{{ route('tools.show', ['uid' => $tool['uid']]) }}">
                            <img src="{{ $tool['preview_image'] ?? $tool['hero_image'] }}" alt="{{ $tool['title'] }}" class="w-full h-full object-cover">
                        </a>
                    </div>
                    <div class="p-4 flex-1">
                        <h3 class="font-semibold text-[var(--color-text-primary)]">
                            <a href="{{ route('tools.show', ['uid' => $tool['uid']]) }}" class="hover:text-[var(--color-primary)]">{{ $tool['title'] }}</a>
                        </h3>
                        @isset($tool['format'])
                            <span class="text-xs text-[var(--color-text-secondary)]">{{ $tool['format'] }}</span>
                        @endisset
                        @isset($tool['teaser'])
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $tool['teaser'] }}</p>
                        @endisset
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</x-layout>
