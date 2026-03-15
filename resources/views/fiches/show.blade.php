<x-layout :title="$fiche->title" :description="$fiche->description ? Str::limit(strip_tags($fiche->description), 160) : 'Praktijkfiche: ' . $fiche->title . ' — een uitgewerkte activiteit op Hartverwarmers.'" :full-width="true">
    @auth
        @if(auth()->user()->isAdmin())
            <flux:modal name="delete-fiche" class="max-w-md">
                <div class="space-y-4">
                    <flux:heading size="lg">Fiche verwijderen?</flux:heading>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Weet je zeker dat je <strong>{{ $fiche->title }}</strong> wilt verwijderen?
                    </p>
                    <div class="flex gap-3 justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuleren</flux:button>
                        </flux:modal.close>
                        <form action="{{ route('fiches.destroy', [$initiative, $fiche]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger">Verwijderen</flux:button>
                        </form>
                    </div>
                </div>
            </flux:modal>
        @endif
    @endauth

    @php
        $hasPreviewCarousel = $fiche->files->contains(fn ($f) => $f->hasPreviewImages());
        $uploadedFiles = $fiche->files->filter(fn ($f) => !$f->isGenerated());
        $fileCount = $fiche->files->count();
        $uploadedFileCount = $uploadedFiles->count();
        $hasGeneratedPdfs = $fileCount > $uploadedFileCount;

        if ($fileCount > 0) {
            $uniqueTypes = $fiche->files->map->typeLabel()->unique();
            $totalSize = $fiche->files->sum('size_bytes');
            $totalMb = $totalSize / (1024 * 1024);
            $totalFormatted = $totalMb >= 1
                ? number_format($totalMb, 1) . ' MB'
                : number_format($totalSize / 1024, 0) . ' KB';
            $sizeLabel = $fileCount === 1
                ? $fiche->files->first()->formattedSize()
                : $totalFormatted;
        }
    @endphp

    {{-- Hero — cream background --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            {{-- Breadcrumbs + admin dropdown --}}
            <div class="mb-6 flex items-center justify-between gap-4">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Initiatieven</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item href="{{ route('initiatives.show', $initiative) }}">{{ $initiative->title }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $fiche->title }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                @auth
                    @if(auth()->user()->isAdmin())
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="cog-6-tooth" icon-trailing="chevron-down" class="text-xs text-[var(--color-text-secondary)]">
                                Admin
                            </flux:button>

                            <flux:menu>
                                @can('update', $fiche)
                                    <flux:menu.item icon="pencil-square" href="{{ route('fiches.edit', $fiche) }}">Bewerk</flux:menu.item>
                                @endcan

                                <form action="{{ route('fiches.toggleDiamond', [$initiative, $fiche]) }}" method="POST">
                                    @csrf
                                    <flux:menu.item type="submit" icon="sparkles">
                                        {{ $fiche->has_diamond ? 'Diamantje verwijderen' : 'Diamantje toekennen' }}
                                    </flux:menu.item>
                                </form>

                                <flux:modal.trigger name="delete-fiche">
                                    <flux:menu.item variant="danger" icon="trash">Verwijder</flux:menu.item>
                                </flux:modal.trigger>

                                <flux:menu.separator />

                                @if($fiche->featured_month)
                                    <flux:menu.heading>
                                        Fiche van de maand &middot; {{ \Carbon\Carbon::createFromFormat('Y-m', $fiche->featured_month)->translatedFormat('M Y') }}
                                    </flux:menu.heading>
                                    <form action="{{ route('fiches.unsetFicheOfMonth', [$initiative, $fiche]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <flux:menu.item type="submit" icon="x-mark">Verwijder als fiche van de maand</flux:menu.item>
                                    </form>
                                @else
                                    <flux:menu.heading>Fiche van de maand</flux:menu.heading>
                                    <div class="px-2 py-1.5">
                                        <form action="{{ route('fiches.setFicheOfMonth', [$initiative, $fiche]) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="month" name="month" value="{{ now()->format('Y-m') }}" class="text-xs font-medium bg-transparent border border-[var(--color-border-light)] rounded-md px-2 py-1 w-[8rem] focus:outline-none text-[var(--color-text-secondary)]" required>
                                            <flux:button type="submit" size="xs" variant="filled">Stel in</flux:button>
                                        </form>
                                    </div>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                @endauth
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12">
                {{-- A: title + meta + description — order-1 on mobile --}}
                <div class="lg:col-span-3 order-1 lg:order-none">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <h1 class="text-5xl">{{ $fiche->title }}</h1>
                        @if($fiche->has_diamond)
                            <x-diamond-badge />
                        @endif
                    </div>

                    {{-- Author --}}
                    @if($fiche->user)
                        <a href="{{ route('contributors.show', $fiche->user) }}" class="flex items-center gap-4 group mb-6">
                            <x-user-avatar :user="$fiche->user" size="lg" class="transition-shadow group-hover:ring-2 group-hover:ring-[var(--color-primary)]/30" />
                            <div>
                                <div class="text-base font-semibold group-hover:text-[var(--color-primary)] transition-colors">
                                    Door {{ $fiche->user->full_name }}@if($fiche->user->organisation) &middot; {{ $fiche->user->organisation }}@endif
                                </div>
                                <div class="text-sm text-[var(--color-text-secondary)]">
                                    Toegevoegd {{ $fiche->created_at->translatedFormat('j M \'y') }}
                                </div>
                            </div>
                        </a>
                    @endif

                    {{-- Meta line --}}
                    <div class="meta-group mb-4">
                        @if($fiche->materials['duration'] ?? null)
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $fiche->materials['duration'] }}
                            </span>
                        @endif

                        @if($fiche->materials['group_size'] ?? null)
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                {{ $fiche->materials['group_size'] }} pers.
                            </span>
                        @endif
                    </div>

                    {{-- Description (lead text) --}}
                    @if($fiche->description)
                        <div class="text-[var(--color-text-secondary)] text-2xl font-light max-w-3xl">
                            {!! $fiche->description !!}
                        </div>
                    @endif

                    {{-- Mobile-only download shortcut --}}
                    @if($uploadedFileCount > 0)
                        <div class="lg:hidden mt-6">
                            <flux:button variant="primary" icon="arrow-down-tray" size="sm"
                                href="{{ route('fiches.download', [$initiative, $fiche]) }}">
                                Download {{ $uploadedFileCount === 1 ? 'bestand' : $uploadedFileCount . ' bestanden' }}@if($hasGeneratedPdfs) (incl. PDF)@endif
                            </flux:button>
                        </div>
                    @endif

                    {{-- Practical information — collapsible preview card --}}
                    @if($fiche->practical_sections)
                        <div class="mt-8" x-data="{ expanded: false }">
                            <div class="rounded-2xl border border-[var(--color-border-light)] overflow-hidden transition-colors duration-200"
                                 :class="expanded ? 'bg-white' : 'bg-[var(--color-bg-cream)] hover:bg-white'">
                                <button @click="expanded = !expanded" class="w-full text-left px-6 py-5 flex items-center gap-4 cursor-pointer group">
                                    <div class="flex-1 min-w-0">
                                        <h2 class="text-2xl" :class="expanded ? '' : 'mb-1'">Praktische informatie</h2>
                                        <div x-show="!expanded" class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                                            @foreach($fiche->practical_sections as $section)
                                                <span class="inline-flex items-center gap-1.5 text-sm text-[var(--color-primary)]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                    {{ $section['title'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center border transition-colors"
                                         :class="expanded ? 'bg-[var(--color-bg-subtle)] border-[var(--color-border-light)]' : 'bg-white border-[var(--color-border-light)] group-hover:border-[var(--color-primary)]'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-text-secondary)] transition-transform duration-200" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                </button>

                                <div x-show="expanded" x-collapse x-cloak>
                                    <div class="px-6 pb-6 space-y-6">
                                        @foreach($fiche->practical_sections as $section)
                                            <div @if(!$loop->first) class="pt-6 border-t border-[var(--color-border-light)]" @endif>
                                                <h3 class="font-heading text-lg font-bold mb-3" style="color: var(--color-primary)">{{ $section['title'] }}</h3>
                                                <div class="practical-content">
                                                    {!! $section['content'] !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- B: preview + download — order-3 on mobile (after description), right column on desktop --}}
                @if($hasPreviewCarousel || $fiche->files->isNotEmpty())
                    <div class="lg:col-span-2 order-3 lg:order-none">
                        @if($hasPreviewCarousel)
                            <div class="lg:sticky lg:top-24">
                                <div class="bg-white rounded-2xl border border-[var(--color-border-light)] overflow-hidden shadow-[0_4px_24px_-4px_rgba(120,90,60,0.08)]">
                                    {{-- Carousel inside the card --}}
                                    <x-file-preview-carousel :files="$fiche->files" />

                                    {{-- Download footer with post-download nudge --}}
                                    @if($fileCount > 0)
                                        <div class="px-5 pb-5 pt-1" x-data="{ downloaded: false }">
                                            {{-- Download button --}}
                                            <a x-show="!downloaded"
                                               href="{{ route('fiches.download', [$initiative, $fiche]) }}"
                                               x-on:click="setTimeout(() => { downloaded = true }, 600)"
                                               class="flex items-center justify-between gap-3 w-full px-5 py-3.5 rounded-xl bg-[var(--color-primary)] text-white font-semibold transition-all hover:bg-[var(--color-primary-hover)] hover:shadow-md active:scale-[0.98] group">
                                                <div class="flex items-center gap-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                    </svg>
                                                    <span>Download {{ $uploadedFileCount === 1 ? 'bestand' : $uploadedFileCount . ' bestanden' }}@if($hasGeneratedPdfs) (incl. PDF)@endif</span>
                                                </div>
                                                <span class="text-sm font-normal text-white/70">{{ $sizeLabel }}</span>
                                            </a>

                                            @include('fiches.partials.post-download-nudge')
                                        </div>
                                    @endif
                                </div>

                                {{-- Kudos & bookmark --}}
                                <div class="mt-4">
                                    <livewire:fiche-kudos :fiche="$fiche" />
                                </div>
                            </div>
                        @else
                            {{-- Files without preview — show file cards + download --}}
                            <div class="space-y-3">
                                @foreach($fiche->files as $file)
                                    @php
                                        $ext = strtoupper(pathinfo($file->original_filename, PATHINFO_EXTENSION));
                                    @endphp
                                    <div class="bg-white rounded-xl p-4 border border-[var(--color-border-light)] flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-[var(--color-bg-accent-light)] flex items-center justify-center shrink-0">
                                            <span class="text-xs font-bold" style="color: var(--color-primary)">{{ $ext }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $file->original_filename }}</p>
                                            <p class="text-xs text-[var(--color-text-secondary)]">
                                                {{ $file->formattedSize() }}
                                                @if($file->isGenerated())
                                                    <span class="section-label text-[10px] ml-1">PDF versie</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="mt-4" x-data="{ downloaded: false }">
                                    <a x-show="!downloaded"
                                       href="{{ route('fiches.download', [$initiative, $fiche]) }}"
                                       x-on:click="setTimeout(() => { downloaded = true }, 600)"
                                       class="flex items-center justify-center gap-2 w-full px-5 py-3 rounded-xl bg-[var(--color-primary)] text-white font-semibold transition-all hover:bg-[var(--color-primary-hover)] hover:shadow-md active:scale-[0.98]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Download
                                    </a>
                                    @include('fiches.partials.post-download-nudge')
                                </div>

                                {{-- Kudos & bookmark --}}
                                <div class="mt-4">
                                    <livewire:fiche-kudos :fiche="$fiche" />
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Content section — comments --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div id="reacties" class="max-w-3xl">
                <livewire:fiche-comments :fiche="$fiche" />
            </div>
        </div>
    </section>

    {{-- Meer fiches --}}
    @if($otherFiches->isNotEmpty())
        <section class="bg-[var(--color-bg-cream)]">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <span class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Meer fiches
                </span>
                <h2 class="mt-1 mb-8">Andere fiches bij {{ $initiative->title }}</h2>

                <div class="space-y-2 max-w-3xl">
                    @foreach($otherFiches as $other)
                        @php
                            $viewed = isset($ficheInteractions[$other->id]) && in_array('view', $ficheInteractions[$other->id]);
                            $downloaded = isset($ficheInteractions[$other->id]) && in_array('download', $ficheInteractions[$other->id]);
                        @endphp
                        <a href="{{ route('fiches.show', [$other->initiative, $other]) }}" class="fiche-list-item {{ $viewed ? 'fiche-list-item-viewed' : '' }}">
                            <x-fiche-icon :fiche="$other" class="fiche-list-icon" />
                            <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $other->title }}</span>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $other->user?->full_name }}@if($other->user?->organisation), {{ $other->user->organisation }}@endif
                                    </span>
                                    @if($other->materials['duration'] ?? null)
                                        <span class="text-xs text-[var(--color-text-secondary)]">&middot; {{ $other->materials['duration'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="fiche-list-kudos {{ $other->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>
                                {{ $other->kudos_count }}
                            </span>
                            @if($downloaded)
                                <span class="fiche-list-downloaded" title="Gedownload">
                                    <flux:icon name="arrow-down-tray" class="size-3.5" />
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    <a href="{{ route('initiatives.show', $initiative) }}" class="cta-link">Alle fiches van {{ $initiative->title }}</a>
                </div>
            </div>
        </section>
    @endif

    <script type="application/ld+json">
    @php
        $steps = collect($fiche->practical_sections)->map(fn ($s, $i) => [
            '@type' => 'HowToStep',
            'position' => $i + 1,
            'name' => $s['title'],
            'text' => strip_tags($s['content']),
        ])->values()->all();

        echo json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $fiche->title,
            'description' => strip_tags($fiche->description ?? ''),
            'author' => [
                '@type' => 'Person',
                'name' => $fiche->user->name,
            ],
            'datePublished' => $fiche->created_at->toIso8601String(),
            'dateModified' => $fiche->updated_at->toIso8601String(),
            'step' => $steps,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    @endphp
    </script>

    {{-- Welcome toast for guest account creation --}}
    <div x-data="{ show: false, name: '' }"
         x-on:guest-welcome.window="name = $event.detail.name; show = true; setTimeout(() => show = false, 4000)"
         class="fixed top-6 left-1/2 -translate-x-1/2 z-50 pointer-events-none">
        <div x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4 scale-96"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="bg-white rounded-2xl px-6 py-4 shadow-[0_8px_32px_rgba(232,118,75,0.15)] border border-[var(--color-primary)]/20 pointer-events-auto">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                    <span x-text="name.charAt(0)"></span>
                </div>
                <div>
                    <p class="font-heading font-bold text-sm text-[var(--color-text-primary)]">Welkom, <span x-text="name"></span>!</p>
                    <p class="text-xs text-[var(--color-text-secondary)]">Je account is aangemaakt. We stuurden een e-mail om je wachtwoord in te stellen.</p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
