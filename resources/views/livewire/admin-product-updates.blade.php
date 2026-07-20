<div>
    {{-- Workflow info, environment-aware: updates live as JSON in git, so only local edits survive a deploy --}}
    @if(app()->environment('production'))
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>Opgelet: wijzigingen hier overleven geen deploy</flux:callout.heading>
            <flux:callout.text>Updates zijn JSON-bestanden die uit git komen; bij de volgende deploy worden ze overschreven. Maak of bewerk updates daarom lokaal op deze pagina, commit het bestand in resources/content/updates/ en deploy.</flux:callout.text>
        </flux:callout>
    @else
        <flux:callout variant="secondary" icon="information-circle" class="mb-6">
            <flux:callout.heading>Zo zet je een update online</flux:callout.heading>
            <flux:callout.text>Bewaar de update hier, commit daarna het JSON-bestand in resources/content/updates/ en deploy. Zo staat de update in git en overleeft ze elke deploy. Bewerk updates niet rechtstreeks op productie.</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <p class="text-sm text-[var(--color-text-secondary)]">
            {{ $this->updates->count() }} {{ $this->updates->count() === 1 ? 'update' : 'updates' }} ·
            de nieuwste verschijnt tot {{ \App\Services\ProductUpdates::FRESH_DAYS }} dagen in de maandmail
        </p>
        @unless($showForm)
            <flux:button variant="primary" icon="plus" wire:click="create">Nieuwe update</flux:button>
        @endunless
    </div>

    {{-- Create / edit form --}}
    @if($showForm)
        <flux:card class="mb-8">
            <form wire:submit="save" class="space-y-5">
                <flux:heading size="lg" class="font-heading font-bold">
                    {{ $originalUid !== null ? 'Update bewerken' : 'Nieuwe update' }}
                </flux:heading>

                <div class="grid sm:grid-cols-2 gap-5">
                    <flux:field>
                        <flux:label>Titel</flux:label>
                        <flux:input wire:model="title" placeholder="Ontdek de themakalender" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Publicatiedatum</flux:label>
                        <flux:input type="date" wire:model="publishedAt" />
                        <flux:error name="publishedAt" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Uid</flux:label>
                    @if($originalUid !== null)
                        <flux:input wire:model="uid" disabled />
                        <flux:description>De uid bepaalt de URL en kan niet meer wijzigen.</flux:description>
                    @else
                        <flux:input wire:model="uid" placeholder="Leeg laten om automatisch af te leiden uit datum en titel" />
                        <flux:description>Wordt de URL: /wat-is-er-nieuw/&lt;uid&gt;. Bijvoorbeeld: 2026-07-themakalender.</flux:description>
                    @endif
                    <flux:error name="uid" />
                </flux:field>

                <flux:field>
                    <flux:label>Korte tekst</flux:label>
                    <flux:textarea wire:model="body" rows="3" />
                    <flux:description>Verschijnt in het overzicht, bovenaan de detailpagina en in de maandmail.</flux:description>
                    <flux:error name="body" />
                </flux:field>

                <flux:field>
                    <flux:label>Uitgebreide tekst (optioneel)</flux:label>
                    <flux:textarea wire:model="content" rows="10" class="font-mono text-sm" placeholder="## Tussentitel&#10;&#10;Markdown voor de detailpagina..." />
                    <flux:description>Markdown. Enkel zichtbaar op de detailpagina.</flux:description>
                    <flux:error name="content" />
                </flux:field>

                <div class="grid sm:grid-cols-2 gap-5">
                    <flux:field>
                        <flux:label>Link-URL (optioneel)</flux:label>
                        <flux:input wire:model="linkUrl" placeholder="/themas" />
                        <flux:error name="linkUrl" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Linktekst</flux:label>
                        <flux:input wire:model="linkLabel" placeholder="Bekijk de themakalender" />
                        <flux:error name="linkLabel" />
                    </flux:field>
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <flux:field>
                        <flux:label>Afbeeldingspad (optioneel)</flux:label>
                        <flux:input wire:model="imageSrc" placeholder="/images/updates/print.webp" />
                        <flux:error name="imageSrc" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Alt-tekst</flux:label>
                        <flux:input wire:model="imageAlt" placeholder="Afgedrukte themakalender op een prikbord" />
                        <flux:error name="imageAlt" />
                    </flux:field>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ $originalUid !== null ? 'Wijzigingen opslaan' : 'Update toevoegen' }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancel">Annuleren</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Updates table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Update</flux:table.column>
            <flux:table.column>Publicatie</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->updates as $update)
                <flux:table.row :key="$update['uid']">
                    <flux:table.cell>
                        <div class="max-w-xs sm:max-w-sm md:max-w-md">
                            <span class="font-medium block truncate">{{ $update['title'] }}</span>
                            <span class="text-xs text-[var(--color-text-secondary)] block truncate">{{ $update['uid'] }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($update['published_at'])->translatedFormat('j M Y') }}
                            @if($this->isFresh($update['published_at']))
                                <flux:badge size="sm" color="orange" inset="top bottom">Nieuw</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button
                                size="sm" variant="ghost" icon="arrow-top-right-on-square"
                                href="{{ route('whats-new.show', $update['uid']) }}" target="_blank"
                                aria-label="Bekijk op de site" tooltip="Bekijk op de site" />
                            <flux:button
                                size="sm" variant="ghost" icon="pencil"
                                wire:click="edit('{{ $update['uid'] }}')"
                                aria-label="Bewerk" tooltip="Bewerk" />
                            <flux:button
                                size="sm" variant="ghost" icon="trash"
                                wire:click="delete('{{ $update['uid'] }}')"
                                wire:confirm="Deze update definitief verwijderen?"
                                aria-label="Verwijder" tooltip="Verwijder" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <span class="text-[var(--color-text-secondary)]">Nog geen updates. Voeg de eerste toe met de knop hierboven.</span>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
