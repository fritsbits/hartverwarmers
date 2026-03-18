<div>
    {{-- Warning banner --}}
    @unless($this->hasFicheOfMonth)
        <flux:callout icon="exclamation-triangle" color="amber" class="mb-6">
            <flux:callout.heading>Geen fiche van de maand voor {{ now()->translatedFormat('F Y') }}</flux:callout.heading>
            <flux:callout.text>Kies er hieronder één uit.</flux:callout.text>
        </flux:callout>
    @endunless

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Zoek op titel..." icon="magnifying-glass" clearable />

        <flux:select wire:model.live="filter">
            <flux:select.option value="">Alle fiches</flux:select.option>
            <flux:select.option value="unassessed">Niet beoordeeld</flux:select.option>
            <flux:select.option value="assessed">Beoordeeld</flux:select.option>
            <flux:select.option value="featured">Eerder uitgelicht</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="initiativeFilter">
            <flux:select.option value="">Alle initiatieven</flux:select.option>
            @foreach($this->initiatives as $id => $title)
                <flux:select.option :value="$id">{{ $title }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->fiches">
        <flux:table.columns>
            <flux:table.column>Fiche</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'completeness_score'" :direction="$sortBy === 'completeness_score' ? $sortDirection : null" wire:click="sortBy('completeness_score')">Volledigheid</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'quality_score'" :direction="$sortBy === 'quality_score' ? $sortDirection : null" wire:click="sortBy('quality_score')">Kwaliteit</flux:table.column>
            <flux:table.column>Kudos</flux:table.column>
            <flux:table.column>Bestanden</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortBy === 'created_at' ? $sortDirection : null" wire:click="sortBy('created_at')">Toegevoegd</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->fiches as $fiche)
                <flux:table.row :key="$fiche->id" wire:click="toggleExpanded({{ $fiche->id }})" class="cursor-pointer {{ $expandedFiche === $fiche->id ? 'bg-white' : ($fiche->featured_month ? 'bg-amber-50' : '') }}">
                    <flux:table.cell>
                        <div>
                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" wire:click.stop class="font-medium hover:text-[var(--color-primary)] transition-colors {{ $expandedFiche === $fiche->id ? 'text-zinc-900 font-bold' : '' }}" title="{{ $fiche->title }}">
                                @if($fiche->featured_month) 🌟 @endif
                                {{ Str::limit($fiche->title, 25) }}
                            </a>
                            <span class="text-xs text-[var(--color-text-secondary)] block">{{ Str::limit($fiche->initiative?->title, 30) }}</span>
                            <span class="text-xs text-[var(--color-text-secondary)] block">door {{ $fiche->user->full_name }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($fiche->completeness_score !== null)
                            <flux:badge size="sm" :color="match(true) {
                                $fiche->completeness_score >= 75 => 'green',
                                $fiche->completeness_score >= 50 => 'yellow',
                                default => 'red',
                            }">{{ $fiche->completeness_score }}%</flux:badge>
                        @else
                            <span class="text-xs text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($fiche->quality_score !== null)
                            <flux:badge size="sm" :color="match(true) {
                                $fiche->quality_score >= 70 => 'green',
                                $fiche->quality_score >= 40 => 'blue',
                                default => 'red',
                            }">{{ $fiche->quality_score }}</flux:badge>
                        @elseif($fiche->quality_assessed_at)
                            <span class="text-xs text-red-400">mislukt</span>
                        @else
                            <span class="text-xs text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm text-zinc-500">{{ $fiche->kudos_count }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm text-zinc-500">{{ $fiche->files_count }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm text-zinc-500">{{ $fiche->created_at->format('d M Y') }}</span>
                        @if($fiche->featured_month)
                            <span class="text-xs font-medium text-amber-700 block">FvdM {{ $fiche->featured_month }}</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>

                {{-- Expanded detail row --}}
                @if($expandedFiche === $fiche->id)
                    <flux:table.row :key="'detail-'.$fiche->id" class="!border-t-0 bg-white">
                        <flux:table.cell colspan="6" class="!pt-0" style="max-width: 0;">
                            <div class="py-2 grid grid-cols-2 gap-4">
                                    {{-- Quality --}}
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase text-zinc-500 mb-1">Kwaliteit @if($fiche->quality_score !== null) — {{ $fiche->quality_score }}/100 @endif</p>
                                        @if($fiche->quality_justification)
                                            <p class="text-sm text-zinc-700 leading-relaxed break-words">{{ $fiche->quality_justification }}</p>
                                            <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click.stop="assess({{ $fiche->id }})" wire:loading.attr="disabled" wire:target="assess({{ $fiche->id }})" class="mt-1.5">
                                                <span wire:loading.remove wire:target="assess({{ $fiche->id }})">Herbeoordeel</span>
                                                <span wire:loading wire:target="assess({{ $fiche->id }})">Bezig...</span>
                                            </flux:button>
                                        @elseif($fiche->quality_assessed_at)
                                            <p class="text-sm text-red-500">Beoordeling mislukt.</p>
                                            <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click.stop="assess({{ $fiche->id }})" wire:loading.attr="disabled" wire:target="assess({{ $fiche->id }})" class="mt-1.5">
                                                <span wire:loading.remove wire:target="assess({{ $fiche->id }})">Opnieuw proberen</span>
                                                <span wire:loading wire:target="assess({{ $fiche->id }})">Bezig...</span>
                                            </flux:button>
                                        @else
                                            <div wire:loading.remove wire:target="assess({{ $fiche->id }})">
                                                <p class="text-sm text-zinc-400">Nog niet beoordeeld.</p>
                                                <flux:button size="xs" variant="ghost" icon="sparkles" wire:click.stop="assess({{ $fiche->id }})" class="mt-1.5">Beoordeel</flux:button>
                                            </div>
                                            <div wire:loading wire:target="assess({{ $fiche->id }})" class="flex items-center gap-2 text-sm text-zinc-500">
                                                <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                                Bezig met beoordelen...
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Completeness --}}
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-zinc-500 mb-1">Volledigheid — {{ $fiche->completeness_score ?? 0 }}%</p>
                                        @if(($fiche->completeness_score ?? 0) < 100)
                                            @php
                                                $materials = $fiche->materials ?? [];
                                                $checks = [
                                                    ['label' => 'Beschrijving (≥100 tekens)', 'pass' => mb_strlen(trim(strip_tags($fiche->description ?? ''))) >= 100],
                                                    ['label' => 'Voorbereiding', 'pass' => trim(strip_tags($materials['preparation'] ?? '')) !== ''],
                                                    ['label' => 'Benodigdheden', 'pass' => trim(strip_tags($materials['inventory'] ?? '')) !== ''],
                                                    ['label' => 'Werkwijze', 'pass' => trim(strip_tags($materials['process'] ?? '')) !== ''],
                                                ];
                                            @endphp
                                            <div class="space-y-0.5">
                                                @foreach($checks as $check)
                                                    @unless($check['pass'])
                                                        <div class="flex items-center gap-1.5 text-sm text-red-500">
                                                            <flux:icon name="x-mark" class="size-3.5" />
                                                            {{ $check['label'] }}
                                                        </div>
                                                    @endunless
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-green-600">Alles ingevuld</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-1 mt-3">
                                    <flux:button size="xs" variant="ghost" icon="eye" href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" wire:click.stop>Bekijk</flux:button>
                                    @if(! $fiche->featured_month)
                                        <flux:button size="xs" variant="ghost" icon="star" wire:click.stop="$set('ficheOfMonthId', {{ $fiche->id }})">Maak FvdM</flux:button>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-8">
                        <div class="text-[var(--color-text-secondary)]">
                            <flux:icon name="magnifying-glass" class="size-8 mx-auto mb-2 opacity-40" />
                            <p>Geen fiches gevonden</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- FvdM month picker modal --}}
    @if($ficheOfMonthId)
        <flux:modal :open="$ficheOfMonthId !== null" @close="$set('ficheOfMonthId', null)">
            <div class="space-y-4">
                <flux:heading>Fiche van de maand instellen</flux:heading>
                <flux:input type="month" wire:model="ficheOfMonthMonth" label="Maand" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('ficheOfMonthId', null)">Annuleer</flux:button>
                    <flux:button variant="primary" wire:click="setFicheOfMonth({{ $ficheOfMonthId }}, '{{ $ficheOfMonthMonth }}')">Bevestig</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
