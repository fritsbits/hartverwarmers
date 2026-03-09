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

    {{-- Admin bar --}}
    @auth
        @if(auth()->user()->isAdmin())
            <div x-data="{ showAdmin: true }">
                <div x-show="showAdmin" x-transition.duration.200ms class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]">
                    <div class="max-w-6xl mx-auto px-6 py-2 flex items-center gap-2.5">
                        {{-- Admin badge --}}
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-widest text-[var(--color-text-secondary)]/60 mr-1 select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            Admin
                        </span>

                        {{-- Actions group --}}
                        <div class="flex items-center gap-px bg-[var(--color-border-light)] rounded-lg overflow-hidden">
                            @can('update', $fiche)
                                <a href="{{ route('fiches.edit', $fiche) }}" class="admin-bar-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    Bewerk
                                </a>
                            @endcan
                            <form action="{{ route('fiches.toggleDiamond', [$initiative, $fiche]) }}" method="POST">
                                @csrf
                                <button type="submit" class="admin-bar-btn {{ $fiche->has_diamond ? 'admin-bar-btn-active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="{{ $fiche->has_diamond ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                                    {{ $fiche->has_diamond ? 'Diamantje' : 'Diamantje' }}
                                </button>
                            </form>
                            <flux:modal.trigger name="delete-fiche">
                                <button class="admin-bar-btn hover:!text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    Verwijder
                                </button>
                            </flux:modal.trigger>
                        </div>

                        {{-- Featured month --}}
                        @if($fiche->featured_month)
                            <div class="flex items-center gap-1.5 rounded-lg bg-amber-50 border border-amber-200/60 px-2.5 py-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="text-xs text-amber-800 font-medium">
                                    Fiche van de maand &middot; {{ \Carbon\Carbon::createFromFormat('Y-m', $fiche->featured_month)->translatedFormat('M Y') }}
                                </span>
                                <form action="{{ route('fiches.unsetFicheOfMonth', [$initiative, $fiche]) }}" method="POST" class="flex">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-amber-400 hover:text-amber-700 transition-colors cursor-pointer -mr-0.5" title="Verwijder als fiche van de maand">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('fiches.setFicheOfMonth', [$initiative, $fiche]) }}" method="POST" class="flex items-center gap-px bg-[var(--color-border-light)] rounded-lg overflow-hidden">
                                @csrf
                                <label class="admin-bar-btn cursor-default !pr-1 gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                                    <input type="month" name="month" value="{{ now()->format('Y-m') }}" class="text-xs font-medium bg-transparent w-[7.5rem] cursor-pointer focus:outline-none text-[var(--color-text-secondary)]" required>
                                </label>
                                <button type="submit" class="admin-bar-btn">
                                    Maak fiche van de maand
                                </button>
                            </form>
                        @endif

                        {{-- Hide toggle --}}
                        <button @click="showAdmin = false" class="ml-auto text-[var(--color-text-secondary)]/40 hover:text-[var(--color-text-secondary)] transition-colors cursor-pointer" title="Verberg admin-balk">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    {{-- Hero — cream background --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            {{-- Breadcrumbs --}}
            <div class="flex items-center justify-between mb-6">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Initiatieven</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item href="{{ route('initiatives.show', $initiative) }}">{{ $initiative->title }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $fiche->title }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                {{-- Left column — title, meta-strip, description --}}
                <div class="lg:col-span-2">
                    <span class="section-label section-label-hero">Fiche</span>

                    <div class="flex flex-wrap items-center gap-3 mt-3 mb-4">
                        <h1 class="text-5xl sm:text-6xl">{{ $fiche->title }}</h1>
                        @if($fiche->has_diamond)
                            <x-diamond-badge />
                        @endif
                    </div>

                    {{-- Meta-strip: duration, group size, goals --}}
                    @if(($fiche->materials['duration'] ?? null) || ($fiche->materials['group_size'] ?? null) || $goalTags->isNotEmpty())
                        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 mt-2">
                            @if($fiche->materials['duration'] ?? null)
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Duur</p>
                                        <p class="text-sm font-medium">{{ $fiche->materials['duration'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($fiche->materials['group_size'] ?? null)
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Groepsgrootte</p>
                                        <p class="text-sm font-medium">{{ $fiche->materials['group_size'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @feature('diamant-goals')
                            @if($goalTags->isNotEmpty())
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[var(--color-bg-subtle)] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: var(--color-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[var(--color-text-secondary)]">Doelen</p>
                                        @php
                                            $facets = config('diamant.facets');
                                            $activeGoalSlugs = $goalTags->pluck('slug')->map(fn ($s) => str_replace('doel-', '', $s))->toArray();
                                        @endphp
                                        <div class="flex flex-wrap gap-1 -mt-0.5">
                                            @foreach($facets as $slug => $facet)
                                                @if(in_array($slug, $activeGoalSlugs))
                                                    <a href="{{ route('goals.show', $slug) }}" class="diamant-pill diamant-pill-sm">
                                                        <x-diamant-gem :letter="$facet['letter']" size="xxs" />
                                                        {{ $facet['keyword'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @endfeature
                        </div>
                    @endif

                    {{-- Description --}}
                    @if($fiche->description)
                        <div class="text-[var(--color-text-secondary)] text-lg font-light mb-6 max-w-2xl prose prose-lg">
                            {!! $fiche->description !!}
                        </div>
                    @endif

                    {{-- Practical information — expandable --}}
                    @if($fiche->practical_sections)
                        @php
                            $sectionTitles = collect($fiche->practical_sections)->pluck('title');
                        @endphp
                        <div x-data="{ open: false }" class="mt-8 rounded-xl border border-[var(--color-border-light)] transition-all duration-200" :class="open ? 'bg-white shadow-card' : 'hover:-translate-y-0.5 hover:bg-white hover:shadow-card hover:border-[var(--color-border-hover)]'">
                            <button @click="open = !open" class="w-full text-left px-5 py-4 flex items-center gap-4 cursor-pointer group">
                                {{-- Clipboard icon in subtle circle --}}
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

                {{-- Right column — author card --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl p-6 shadow-card">
                        {{-- Author --}}
                        @if($fiche->user)
                            <div class="flex flex-col items-center text-center mb-5">
                                <a href="{{ route('contributors.show', $fiche->user) }}" class="flex flex-col items-center text-center group">
                                    @if($fiche->user->avatar_path)
                                        <img src="{{ $fiche->user->avatarUrl() }}" alt="{{ $fiche->user->full_name }}" class="w-16 h-16 rounded-full object-cover mb-3 transition-shadow group-hover:ring-2 group-hover:ring-[var(--color-primary)]/30">
                                    @else
                                        <div class="w-16 h-16 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl font-semibold mb-3 transition-shadow group-hover:ring-2 group-hover:ring-[var(--color-primary)]/30">
                                            {{ substr($fiche->user->first_name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="font-semibold group-hover:text-[var(--color-primary)] transition-colors">{{ $fiche->user->full_name }}</div>
                                </a>
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
                        @endif

                        {{-- Kudos & bookmark --}}
                        <div class="border-t border-[var(--color-border-light)] pt-5 {{ ($fiche->download_count ?? 0) > 0 || ($fiche->comments_count ?? 0) > 0 ? 'mb-5' : '' }}">
                            <livewire:fiche-kudos :fiche="$fiche" />
                        </div>

                        {{-- Meta info --}}
                        @if(($fiche->download_count ?? 0) > 0 || ($fiche->comments_count ?? 0) > 0)
                            <div class="border-t border-[var(--color-border-light)] pt-5 space-y-2 text-sm text-[var(--color-text-secondary)]">
                                @if($fiche->download_count > 0)
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        {{ $fiche->download_count }} keer gedownload
                                    </div>
                                @endif
                                @if($fiche->comments_count > 0)
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                        </svg>
                                        {{ $fiche->comments_count }} {{ $fiche->comments_count === 1 ? 'reactie' : 'reacties' }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content section --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16 lg:grid lg:grid-cols-3 lg:gap-12">
            <div class="lg:col-span-2">
                {{-- Files --}}
                @if($fiche->files->isNotEmpty())
                    <div class="pb-10 mb-10 border-b border-[var(--color-border-light)]">
                        <span class="section-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Bestanden
                        </span>

                        @php
                            $fileCount = $fiche->files->count();
                            $uniqueTypes = $fiche->files->map->typeLabel()->unique();
                            $downloadTitle = match (true) {
                                $fileCount === 1 => 'Download ' . $uniqueTypes->first(),
                                $uniqueTypes->count() === 1 => 'Download ' . $fileCount . ' ' . Str::lower($uniqueTypes->first()) . '-bestanden',
                                default => 'Download materiaal',
                            };
                            $totalSize = $fiche->files->sum('size_bytes');
                            $totalMb = $totalSize / (1024 * 1024);
                            $totalFormatted = $totalMb >= 1
                                ? number_format($totalMb, 1) . ' MB'
                                : number_format($totalSize / 1024, 0) . ' KB';
                            $sizeLabel = $fileCount === 1
                                ? $fiche->files->first()->formattedSize()
                                : $totalFormatted;
                        @endphp

                        {{-- Header row: title + pills left, download button right --}}
                        <div class="flex flex-wrap items-start justify-between gap-4 mt-1 mb-4">
                            <div>
                                <h2>{{ $downloadTitle }}</h2>
                                @if($fileCount > 1)
                                    @php
                                        $grouped = $fiche->files->groupBy(fn ($f) => strtoupper(pathinfo($f->original_filename, PATHINFO_EXTENSION)));
                                    @endphp
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach($grouped as $ext => $group)
                                            <flux:tooltip position="top">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--color-bg-subtle)] text-sm text-[var(--color-text-secondary)] font-medium cursor-default">
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

                            <flux:button variant="primary" icon="arrow-down-tray"
                                href="{{ route('fiches.download', [$initiative, $fiche]) }}">
                                Download
                                <span class="text-xs font-normal text-white/70">({{ $sizeLabel }})</span>
                            </flux:button>
                        </div>

                        @if($fiche->files->contains(fn ($f) => $f->hasPreviewImages()))
                            @php
                                $carouselSizeFormatted = $totalMb >= 1
                                    ? number_format($totalMb, 0) . ' MB'
                                    : number_format($totalSize / 1024, 0) . ' KB';
                            @endphp
                            <x-file-preview-carousel :files="$fiche->files" :download-url="route('fiches.download', [$initiative, $fiche])" download-label="Download" :download-size="$carouselSizeFormatted" />
                        @endif
                    </div>
                @endif

                {{-- Comments --}}
                <div id="reacties">
                    <livewire:fiche-comments :fiche="$fiche" />
                </div>
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
                <h2 class="mt-3 mb-8">Andere fiches bij {{ $initiative->title }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($otherFiches as $other)
                        <x-fiche-card :fiche="$other" />
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
