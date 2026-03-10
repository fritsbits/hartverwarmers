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
        $fileCount = $fiche->files->count();

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
                {{-- A: label + h1 + meta strip — order-1 on mobile --}}
                <div class="lg:col-span-3 order-1 lg:order-none">
                    <span class="section-label section-label-hero">Fiche</span>

                    <div class="flex flex-wrap items-center gap-3 mt-1 mb-4">
                        <h1 class="text-5xl">{{ $fiche->title }}</h1>
                        @if($fiche->has_diamond)
                            <x-diamond-badge />
                        @endif
                    </div>

                    {{-- Compact meta strip --}}
                    @if(($fiche->materials['duration'] ?? null) || ($fiche->materials['group_size'] ?? null) || $fileCount > 0 || ($fiche->download_count ?? 0) > 0)
                        <div class="meta-group mb-6">
                            @if($fiche->materials['duration'] ?? null)
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $fiche->materials['duration'] }}
                                </span>
                            @endif

                            @if($fiche->materials['group_size'] ?? null)
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    {{ $fiche->materials['group_size'] }}
                                </span>
                            @endif

                            @if($fileCount > 0)
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    {{ $fileCount }} {{ $fileCount === 1 ? 'bestand' : 'bestanden' }}
                                </span>
                            @endif

                            @if(($fiche->download_count ?? 0) > 0)
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    {{ $fiche->download_count }} keer gedownload
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- B: preview + download — order-2 on mobile, right column on desktop --}}
                @if($hasPreviewCarousel || $fiche->files->isNotEmpty())
                    <div class="lg:col-span-2 lg:row-span-2 order-2 lg:order-none">
                        @if($hasPreviewCarousel)
                            <div class="lg:sticky lg:top-24">
                                <x-file-preview-carousel :files="$fiche->files" />

                                {{-- Download button below carousel --}}
                                @if($fileCount > 0)
                                    <div class="flex justify-center">
                                        <flux:button variant="primary" icon="arrow-down-tray"
                                            href="{{ route('fiches.download', [$initiative, $fiche]) }}">
                                            Download
                                            <span class="text-xs font-normal text-white/70">({{ $sizeLabel }})</span>
                                        </flux:button>
                                    </div>
                                @endif

                                {{-- File type pills below download --}}
                                @if($fileCount > 1)
                                    @php
                                        $grouped = $fiche->files->groupBy(fn ($f) => strtoupper(pathinfo($f->original_filename, PATHINFO_EXTENSION)));
                                    @endphp
                                    <div class="flex flex-wrap justify-center gap-2 mt-2">
                                        @foreach($grouped as $ext => $group)
                                            <flux:tooltip position="top">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-sm text-[var(--color-text-secondary)] font-medium cursor-default border border-[var(--color-border-light)]">
                                                    {{ $group->count() > 1 ? $group->count() . '× ' : '' }}{{ $ext }}
                                                </span>
                                                <flux:tooltip.content class="max-w-xs">
                                                    {{ $group->pluck('original_filename')->join(', ') }}
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        @endforeach
                                    </div>
                                @endif
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
                                            <p class="text-xs text-[var(--color-text-secondary)]">{{ $file->formattedSize() }}</p>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="mt-4 flex justify-center">
                                    <flux:button variant="primary" icon="arrow-down-tray"
                                        href="{{ route('fiches.download', [$initiative, $fiche]) }}">
                                        Download
                                        <span class="text-xs font-normal text-white/70">({{ $sizeLabel }})</span>
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- C: description + author + kudos + practical info — order-3 on mobile --}}
                <div class="lg:col-span-3 order-3 lg:order-none">
                    {{-- Description --}}
                    @if($fiche->description)
                        <div class="text-2xl font-light mb-6 max-w-2xl leading-relaxed" style="color: var(--color-text-secondary)">
                            {!! $fiche->description !!}
                        </div>
                    @endif

                    {{-- Author --}}
                    @if($fiche->user)
                        <div class="mt-2">
                            <span class="text-meta text-xs uppercase tracking-wider font-semibold">Toegevoegd door</span>
                            <a href="{{ route('contributors.show', $fiche->user) }}" class="flex items-center gap-3 group mt-2">
                                @if($fiche->user->avatar_path)
                                    <img src="{{ $fiche->user->avatarUrl() }}" alt="{{ $fiche->user->full_name }}" class="w-10 h-10 rounded-full object-cover transition-shadow group-hover:ring-2 group-hover:ring-[var(--color-primary)]/30">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-lg font-semibold transition-shadow group-hover:ring-2 group-hover:ring-[var(--color-primary)]/30">
                                        {{ substr($fiche->user->first_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-base font-semibold group-hover:text-[var(--color-primary)] transition-colors">{{ $fiche->user->full_name }}</div>
                                    <div class="text-sm text-[var(--color-text-secondary)]">
                                        @if($fiche->user->function_title)
                                            {{ $fiche->user->function_title }}
                                        @endif
                                        @if($fiche->user->function_title && $fiche->user->organisation)
                                            &middot;
                                        @endif
                                        @if($fiche->user->organisation)
                                            {{ $fiche->user->organisation }}
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif

                    {{-- Kudos & bookmark --}}
                    <div class="mt-6 pt-6 border-t border-[var(--color-border-light)]">
                        <livewire:fiche-kudos :fiche="$fiche" />
                    </div>

                    {{-- Practical information — expandable --}}
                    @if($fiche->practical_sections)
                        @php
                            $sectionTitles = collect($fiche->practical_sections)->pluck('title');
                        @endphp
                        <div x-data="{ open: false }" class="mt-8 mb-8 rounded-xl border border-[var(--color-border-light)] transition-all duration-200" :class="open ? 'bg-white shadow-card' : 'hover:-translate-y-0.5 hover:bg-white hover:shadow-card hover:border-[var(--color-border-hover)]'">
                            <button @click="open = !open" class="w-full text-left px-5 py-4 flex items-center gap-4 cursor-pointer group">
                                <div class="w-12 h-12 rounded-full bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h2 class="font-heading text-[17px] font-bold !mb-0">Praktische informatie</h2>
                                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $sectionTitles->join(' · ') }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="open" x-collapse x-cloak>
                                <div class="px-5 pb-6 border-t border-[var(--color-border-light)]">
                                    <div class="divide-y divide-[var(--color-border-light)]">
                                        @foreach($fiche->practical_sections as $section)
                                            <div class="pt-5 {{ !$loop->last ? 'pb-5' : '' }}">
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
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content section — comments --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div id="reacties" class="max-w-3xl">
                <livewire:fiche-comments :fiche="$fiche" />
            </div>
        </div>
    </section>

    {{-- Meer fiches --}}
    @if($otherFiches->isNotEmpty())
        <hr class="border-[var(--color-border-light)]">

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
                        <a href="{{ route('fiches.show', [$other->initiative, $other]) }}" class="fiche-list-item">
                            <span class="fiche-list-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </span>
                            <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                <span class="font-body font-semibold text-base text-[var(--color-text-primary)] truncate">{{ $other->title }}</span>
                                <span class="text-xs text-[var(--color-text-secondary)]">
                                    {{ $other->user?->full_name }}@if($other->user?->organisation), {{ $other->user->organisation }}@endif
                                </span>
                            </div>
                            <span class="fiche-list-kudos {{ $other->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>
                                {{ $other->kudos_count }}
                            </span>
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
</x-layout>
