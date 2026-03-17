<div>
    {{-- Warning banner --}}
    @unless($this->hasFicheOfMonth)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 mb-6 flex items-center gap-3">
            <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 shrink-0" />
            <p class="text-sm text-amber-800">
                <strong>Geen fiche van de maand voor {{ now()->translatedFormat('F Y') }}.</strong>
                Kies er hieronder één uit.
            </p>
        </div>
    @endunless

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Zoek op titel..." icon="magnifying-glass" clearable />
        </div>

        <flux:select wire:model.live="filter" class="sm:w-48">
            <flux:select.option value="">Alle fiches</flux:select.option>
            <flux:select.option value="unassessed">Niet beoordeeld</flux:select.option>
            <flux:select.option value="assessed">Beoordeeld</flux:select.option>
            <flux:select.option value="featured">Eerder uitgelicht</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="initiativeFilter" class="sm:w-56">
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
            <flux:table.column>Initiatief</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'completeness_score'" :direction="$sortBy === 'completeness_score' ? $sortDirection : null" wire:click="sortBy('completeness_score')">Volledigheid</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'quality_score'" :direction="$sortBy === 'quality_score' ? $sortDirection : null" wire:click="sortBy('quality_score')">Kwaliteit</flux:table.column>
            <flux:table.column>Kudos</flux:table.column>
            <flux:table.column>Bestanden</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortBy === 'created_at' ? $sortDirection : null" wire:click="sortBy('created_at')">Toegevoegd</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->fiches as $fiche)
                <flux:table.row :key="$fiche->id" wire:click="toggleExpanded({{ $fiche->id }})" class="cursor-pointer {{ $fiche->featured_month ? 'bg-amber-50' : '' }}">
                    <flux:table.cell>
                        <div>
                            <span class="font-medium">
                                @if($fiche->featured_month) 🌟 @endif
                                {{ $fiche->title }}
                            </span>
                            <span class="text-xs text-[var(--color-text-secondary)] block">door {{ $fiche->user->full_name }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm">{{ $fiche->initiative?->title }}</span>
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
                            <span class="text-xs text-zinc-400">wacht…</span>
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
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($fiche->featured_month)
                            <span class="text-xs font-medium text-amber-700">FvdM {{ $fiche->featured_month }}</span>
                        @else
                            <flux:button size="xs" variant="primary" wire:click.stop="$set('ficheOfMonthId', {{ $fiche->id }})">
                                Maak FvdM
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>

                {{-- Expanded detail row --}}
                @if($expandedFiche === $fiche->id)
                    <flux:table.row :key="'detail-'.$fiche->id">
                        <flux:table.cell colspan="8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                                {{-- Quality --}}
                                <div>
                                    <p class="text-xs font-semibold uppercase text-zinc-500 mb-2">
                                        Kwaliteitsscore
                                        @if($fiche->quality_score !== null) — {{ $fiche->quality_score }}/100 @endif
                                    </p>
                                    @if($fiche->quality_justification)
                                        <div class="bg-zinc-50 rounded-lg p-3 text-sm text-zinc-700 leading-relaxed">
                                            {{ $fiche->quality_justification }}
                                        </div>
                                    @elseif($fiche->quality_assessed_at)
                                        <p class="text-sm text-red-500">Beoordeling mislukt.</p>
                                    @else
                                        <p class="text-sm text-zinc-400">Nog niet beoordeeld.</p>
                                    @endif
                                </div>

                                {{-- Completeness --}}
                                <div>
                                    <p class="text-xs font-semibold uppercase text-zinc-500 mb-2">
                                        Volledigheid — {{ $fiche->completeness_score ?? 0 }}%
                                    </p>
                                    @php
                                        $materials = $fiche->materials ?? [];
                                        $checks = [
                                            ['label' => 'Beschrijving (≥100 tekens)', 'pass' => mb_strlen(trim(strip_tags($fiche->description ?? ''))) >= 100],
                                            ['label' => 'Voorbereiding', 'pass' => trim(strip_tags($materials['preparation'] ?? '')) !== ''],
                                            ['label' => 'Benodigdheden', 'pass' => trim(strip_tags($materials['inventory'] ?? '')) !== ''],
                                            ['label' => 'Werkwijze', 'pass' => trim(strip_tags($materials['process'] ?? '')) !== ''],
                                        ];
                                    @endphp
                                    <div class="space-y-1.5">
                                        @foreach($checks as $check)
                                            <div class="flex items-center gap-2 text-sm">
                                                @if($check['pass'])
                                                    <flux:icon name="check" class="size-4 text-green-600" />
                                                @else
                                                    <flux:icon name="x-mark" class="size-4 text-red-400" />
                                                @endif
                                                {{ $check['label'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Overflow menu --}}
                            <div class="flex justify-end pt-2 border-t border-zinc-100">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click.stop="reassess({{ $fiche->id }})" icon="arrow-path">Herbeoordeel</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center py-8">
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
