<div x-on:modal-show.window="if ($event.detail.name === 'search') setTimeout(() => $el.querySelector('[data-flux-command-input] input')?.focus(), 50)">
    <flux:modal name="search" variant="bare" class="w-full max-w-[34rem] my-[12vh] max-h-screen overflow-y-hidden">
        <flux:command class="shadow-lg inline-flex flex-col max-h-[76vh] [&_ui-option-empty]:!hidden">
            <flux:command.input wire:model.live.debounce.300ms="query" placeholder="Zoek initiatieven en fiches..." closable />

            <flux:command.items>
                @if(strlen(trim($query)) < 2)
                    <div class="px-4 py-8 text-center text-sm text-[var(--color-text-secondary)]">
                        Typ minstens 2 tekens om te zoeken...
                    </div>
                @elseif(!$this->hasResults)
                    <div class="px-4 py-8 text-center text-sm text-[var(--color-text-secondary)]">
                        Geen resultaten gevonden voor "{{ $query }}"
                    </div>
                @else
                    @if($this->results['initiatives']->isNotEmpty())
                        <div class="px-4 pt-3 pb-1">
                            <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Initiatieven</span>
                        </div>

                        <div class="divide-y divide-[var(--color-border-light)]">
                            @foreach($this->results['initiatives'] as $initiative)
                                <a wire:key="initiative-{{ $initiative->id }}" href="{{ route('initiatives.show', $initiative) }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                    <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-sm text-[var(--color-text-primary)]">{{ $initiative->title }}</span>
                                        @if($initiative->description)
                                            <p class="text-xs text-[var(--color-text-secondary)] line-clamp-1">{{ Str::limit($initiative->description, 80) }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($this->results['fiches']->isNotEmpty())
                        @if($this->results['initiatives']->isNotEmpty())
                            <div class="border-b border-[var(--color-border-light)]"></div>
                        @endif

                        <div class="px-4 pt-3 pb-1">
                            <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Fiches</span>
                        </div>

                        <div class="divide-y divide-[var(--color-border-light)]">
                            @foreach($this->results['fiches'] as $fiche)
                                <a wire:key="fiche-{{ $fiche->id }}" href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                                    <x-fiche-icon :fiche="$fiche" size="sm" />
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-sm text-[var(--color-text-primary)]">{{ $fiche->title }}</span>
                                        @if($fiche->description)
                                            <p class="text-xs text-[var(--color-text-secondary)] line-clamp-1">{{ Str::limit($fiche->description, 80) }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif
            </flux:command.items>
        </flux:command>
    </flux:modal>
</div>
