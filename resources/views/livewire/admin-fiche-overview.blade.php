<div>
    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Zoek op titel..." icon="magnifying-glass" clearable />

        <flux:select wire:model.live="filter">
            <flux:select.option value="">Alle fiches</flux:select.option>
            <flux:select.option value="unassessed">Niet beoordeeld</flux:select.option>
            <flux:select.option value="assessed">Beoordeeld</flux:select.option>
            <flux:select.option disabled>────────────</flux:select.option>
            <flux:select.option value="q-strong">Sterke kern</flux:select.option>
            <flux:select.option value="q-quickwin">Goede activiteit, zwakke fiche</flux:select.option>
            <flux:select.option value="q-wellwritten">Goed geschreven, zwak concept</flux:select.option>
            <flux:select.option value="q-needswork">Zwak op beide vlakken</flux:select.option>
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
            <flux:table.column sortable :sorted="$sortBy === 'combined_score'" :direction="$sortBy === 'combined_score' ? $sortDirection : null" wire:click="sort('combined_score')">Score</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'quality_score'" :direction="$sortBy === 'quality_score' ? $sortDirection : null" wire:click="sort('quality_score')">Kwaliteit</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'presentation_score'" :direction="$sortBy === 'presentation_score' ? $sortDirection : null" wire:click="sort('presentation_score')">Presentatie</flux:table.column>
            <flux:table.column>Kudos</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->fiches as $fiche)
                <flux:table.row :key="$fiche->id" wire:click="toggleExpanded({{ $fiche->id }})" class="cursor-pointer {{ $expandedFiche === $fiche->id ? 'bg-white' : '' }}">
                    <flux:table.cell>
                        <div>
                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" wire:click.stop class="font-medium hover:text-[var(--color-primary)] transition-colors {{ $expandedFiche === $fiche->id ? 'text-zinc-900 font-bold' : '' }}" title="{{ $fiche->title }}">
                                @if($fiche->has_diamond)<flux:icon name="sparkles" class="size-3.5 inline-block text-amber-500 -mt-0.5 mr-0.5" />@endif{{ Str::limit($fiche->title, 25) }}
                            </a>
                            <span class="text-xs text-[var(--color-text-secondary)] block">{{ Str::limit($fiche->initiative?->title, 30) }}</span>
                            <span class="text-xs text-[var(--color-text-secondary)] block">door {{ $fiche->user->full_name }} · {{ $fiche->created_at->format('d M Y') }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($fiche->quality_score !== null && $fiche->presentation_score !== null)
                            @php $combined = $fiche->quality_score + $fiche->presentation_score; @endphp
                            <flux:badge size="sm" :color="match(true) {
                                $combined >= 140 => 'green',
                                $combined >= 80 => 'blue',
                                default => 'red',
                            }">{{ $combined }}</flux:badge>
                        @elseif($fiche->quality_assessed_at)
                            <span class="text-xs text-red-400">mislukt</span>
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
                        @if($fiche->presentation_score !== null)
                            <flux:badge size="sm" :color="match(true) {
                                $fiche->presentation_score >= 70 => 'green',
                                $fiche->presentation_score >= 40 => 'blue',
                                default => 'red',
                            }">{{ $fiche->presentation_score }}</flux:badge>
                        @elseif($fiche->quality_assessed_at)
                            <span class="text-xs text-red-400">mislukt</span>
                        @else
                            <span class="text-xs text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm text-zinc-500">{{ $fiche->kudos_count }}</span>
                    </flux:table.cell>

                </flux:table.row>

                {{-- Expanded detail row --}}
                @if($expandedFiche === $fiche->id)
                    <flux:table.row :key="'detail-'.$fiche->id" class="!border-t-0 bg-white">
                        <flux:table.cell colspan="5" class="!pt-0">
                            {{-- Quality --}}
                            <p class="text-xs font-semibold uppercase text-zinc-500 mb-1">Kwaliteit @if($fiche->quality_score !== null) — {{ $fiche->quality_score }}/100 @endif</p>
                            @if($fiche->quality_justification)
                                <p class="text-sm text-zinc-700 leading-relaxed" style="max-width: 500px; text-wrap: auto;">{{ $fiche->quality_justification }}</p>
                            @elseif($fiche->quality_assessed_at)
                                <p class="text-sm text-red-500">Beoordeling mislukt.</p>
                            @else
                                <p class="text-sm text-zinc-400">Nog niet beoordeeld.</p>
                            @endif

                            {{-- Presentation --}}
                            @if($fiche->presentation_justification)
                                <p class="text-xs font-semibold uppercase text-zinc-500 mb-1 mt-3">Presentatie — {{ $fiche->presentation_score }}/100</p>
                                <p class="text-sm text-zinc-700 leading-relaxed" style="max-width: 500px; text-wrap: auto;">{{ $fiche->presentation_justification }}</p>
                            @endif

                            {{-- Completeness (only show missing) --}}
                            @if(($fiche->completeness_score ?? 0) < 100)
                                @php
                                    $materials = $fiche->materials ?? [];
                                    $missing = collect([
                                        ['label' => 'beschrijving (≥100 tekens)', 'pass' => mb_strlen(trim(strip_tags($fiche->description ?? ''))) >= 100],
                                        ['label' => 'voorbereiding', 'pass' => trim(strip_tags($materials['preparation'] ?? '')) !== ''],
                                        ['label' => 'benodigdheden', 'pass' => trim(strip_tags($materials['inventory'] ?? '')) !== ''],
                                        ['label' => 'werkwijze', 'pass' => trim(strip_tags($materials['process'] ?? '')) !== ''],
                                    ])->reject(fn ($c) => $c['pass'])->pluck('label');
                                @endphp
                                <p class="text-sm text-zinc-500 mt-2">Ontbreekt: <span class="text-red-500">{{ $missing->implode(', ') }}</span></p>
                            @endif

                            {{-- Assess / Actions --}}
                            @if($fiche->quality_assessed_at)
                                <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click.stop="assess({{ $fiche->id }})" wire:loading.attr="disabled" wire:target="assess({{ $fiche->id }})" class="mt-2">
                                    <span wire:loading.remove wire:target="assess({{ $fiche->id }})">Herbeoordeel</span>
                                    <span wire:loading wire:target="assess({{ $fiche->id }})">Bezig...</span>
                                </flux:button>
                            @else
                                <div wire:loading.remove wire:target="assess({{ $fiche->id }})" class="mt-2">
                                    <flux:button size="xs" variant="ghost" icon="sparkles" wire:click.stop="assess({{ $fiche->id }})">Beoordeel</flux:button>
                                </div>
                                <div wire:loading wire:target="assess({{ $fiche->id }})" class="flex items-center gap-2 text-sm text-zinc-500 mt-2">
                                    <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                    Bezig met beoordelen...
                                </div>
                            @endif

                            <div class="flex items-center gap-1 mt-2">
                                <flux:button size="xs" variant="ghost" icon="eye" href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" wire:click.stop>Bekijk</flux:button>
                                <flux:button size="xs" :variant="$fiche->has_diamond ? 'filled' : 'ghost'" icon="sparkles" wire:click.stop="toggleDiamond({{ $fiche->id }})">
                                    {{ $fiche->has_diamond ? 'Diamant verwijderen' : 'Maak diamant' }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-8">
                        <div class="text-[var(--color-text-secondary)]">
                            <flux:icon name="magnifying-glass" class="size-8 mx-auto mb-2 opacity-40" />
                            <p>Geen fiches gevonden</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

</div>
