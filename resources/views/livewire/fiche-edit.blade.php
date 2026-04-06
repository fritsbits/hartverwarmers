<div>
    @if(! $fiche->published)
        <div class="mb-6 flex items-center gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <flux:icon.pencil-square class="size-4 shrink-0" />
            <span>Dit is een <strong>concept</strong> — nog niet zichtbaar voor anderen.</span>
        </div>
    @endif

    <div class="space-y-8">

        {{-- Top zone: Title + Description (always visible) --}}
        <div class="bg-white rounded-xl border border-[var(--color-border-light)] shadow-[var(--shadow-card)] p-6 space-y-6">
            @php
                $hasTitleSuggestion = !empty($aiSuggestions['title']);
                $isTitleApplied = in_array('title', $appliedSuggestions);
            @endphp
            <div class="grid grid-cols-1 @if($hasTitleSuggestion) lg:grid-cols-12 @endif gap-8">
                <div class="@if($hasTitleSuggestion) lg:col-span-7 @endif">
                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Titel</flux:label>
                        <flux:description class="text-sm">Wees specifiek — wat maakt jouw activiteit uniek of bijzonder?</flux:description>
                        <flux:input wire:model="title" class="text-base" placeholder="bijv. Muziekbingo met schlagers uit de jaren '60" />
                        <flux:error name="title" />
                    </flux:field>
                </div>
                @if($hasTitleSuggestion)
                    <div class="lg:col-span-5">
                        <x-ai-suggestion-panel
                            :suggestion="e($aiSuggestions['title'])"
                            :rawSuggestion="$aiSuggestions['title']"
                            field="title"
                            :is-applied="$isTitleApplied"
                        />
                    </div>
                @endif
            </div>

            <hr class="border-[var(--color-border-light)]">

            @php
                $hasDescriptionSuggestion = !empty($aiSuggestions['description']);
                $isDescriptionApplied = in_array('description', $appliedSuggestions);
            @endphp
            <div class="grid grid-cols-1 @if($hasDescriptionSuggestion) lg:grid-cols-12 @endif gap-8">
                <div class="@if($hasDescriptionSuggestion) lg:col-span-7 @endif">
                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Beschrijving</flux:label>
                        <flux:description class="text-sm">Wat is de activiteit, en wat maakt ze bijzonder? Schrijf 1 à 3 zinnen — concreet én met een menselijke touch.</flux:description>
                        <flux:textarea
                            wire:model="description"
                            rows="3"
                            placeholder="bijv. Samen naar de beenhouwerij en een eigen soeprecept maken — een activiteit die een bewoner terugbrengt naar wie ze was."
                        />
                        <flux:error name="description" />
                    </flux:field>
                </div>
                @if($hasDescriptionSuggestion)
                    <div class="lg:col-span-5">
                        <x-ai-suggestion-panel
                            :suggestion="$aiSuggestions['description']"
                            field="description"
                            :is-applied="$isDescriptionApplied"
                            :rawSuggestion="$aiSuggestions['description']"
                        />
                    </div>
                @endif
            </div>

            <hr class="border-[var(--color-border-light)]">

            <flux:field>
                <flux:label class="text-base font-body font-bold">Aanleiding &amp; verhaal <span class="field-tag ml-1">Optioneel</span></flux:label>
                <flux:description class="text-sm">Hoe groeide dit idee? Vertel over het moment, de context of de bewoner(s) die aan de basis lagen.</flux:description>
                <div
                    x-data="{ expanded: @js(!empty($aanleiding)) }"
                    @click="expanded = true"
                    class="grid motion-safe:[transition:grid-template-rows_0.3s_cubic-bezier(0.25,1,0.5,1)]"
                    :class="expanded ? 'grid-rows-[1fr] overflow-visible' : 'grid-rows-[100px] overflow-hidden cursor-text'"
                >
                    <flux:editor
                        wire:model="aanleiding"
                        toolbar="bold | bullet ordered | link"
                        placeholder="bijv. Dit idee groeide tijdens een vorming waarbij we met een bewoner over haar vroegere beroep als slager in gesprek gingen…"
                    />
                </div>
                <flux:error name="aanleiding" />
            </flux:field>
        </div>

        {{-- Tabbed sections --}}
        <flux:tab.group>
            <flux:tabs wire:model="activeTab">
                <flux:tab name="praktische-informatie" icon="clipboard-document-list">Praktische informatie</flux:tab>
                <flux:tab name="bestanden" icon="document-text">Bestanden</flux:tab>
                <flux:tab name="details" icon="adjustments-horizontal">Details</flux:tab>
            </flux:tabs>

            {{-- Tab: Praktische informatie --}}
            <flux:tab.panel name="praktische-informatie">
                <div class="space-y-6 pt-2">
                    @php
                        $hasPreparationSuggestion = !empty($aiSuggestions['preparation']);
                        $isPreparationApplied = in_array('preparation', $appliedSuggestions);
                    @endphp
                    <div class="grid grid-cols-1 @if($hasPreparationSuggestion) lg:grid-cols-12 @endif gap-8">
                        <div class="@if($hasPreparationSuggestion) lg:col-span-7 @endif">
                            <flux:field>
                                <flux:label class="text-base font-body font-bold">Voorbereiding</flux:label>
                                <flux:description class="text-sm">Wat moet er klaargezet of voorbereid worden?</flux:description>
                                <div
                                    x-data="{ expanded: @js(!empty($preparation)) }"
                                    @click="expanded = true"
                                    class="grid motion-safe:[transition:grid-template-rows_0.3s_cubic-bezier(0.25,1,0.5,1)]"
                                    :class="expanded ? 'grid-rows-[1fr] overflow-visible' : 'grid-rows-[100px] overflow-hidden cursor-text'"
                                >
                                    <flux:editor wire:model="preparation" toolbar="bold | bullet ordered | link" placeholder="bijv. Print de bingokaarten uit en test het geluid van de muziekinstallatie." />
                                </div>
                            </flux:field>
                        </div>
                        @if($hasPreparationSuggestion)
                            <div class="lg:col-span-5">
                                <x-ai-suggestion-panel
                                    :suggestion="$aiSuggestions['preparation']"
                                    field="preparation"
                                    :is-applied="$isPreparationApplied"
                                    :rawSuggestion="$aiSuggestions['preparation']"
                                />
                            </div>
                        @endif
                    </div>

                    <hr class="border-[var(--color-border-light)]">

                    @php
                        $hasInventorySuggestion = !empty($aiSuggestions['inventory']);
                        $isInventoryApplied = in_array('inventory', $appliedSuggestions);
                    @endphp
                    <div class="grid grid-cols-1 @if($hasInventorySuggestion) lg:grid-cols-12 @endif gap-8">
                        <div class="@if($hasInventorySuggestion) lg:col-span-7 @endif">
                            <flux:field>
                                <flux:label class="text-base font-body font-bold">Benodigdheden</flux:label>
                                <flux:description class="text-sm">Welke materialen heb je nodig?</flux:description>
                                <div
                                    x-data="{ expanded: @js(!empty($inventory)) }"
                                    @click="expanded = true"
                                    class="grid motion-safe:[transition:grid-template-rows_0.3s_cubic-bezier(0.25,1,0.5,1)]"
                                    :class="expanded ? 'grid-rows-[1fr] overflow-visible' : 'grid-rows-[100px] overflow-hidden cursor-text'"
                                >
                                    <flux:editor wire:model="inventory" toolbar="bold | bullet ordered | link" placeholder="bijv. Bingokaarten, stiften, muziekinstallatie, prijsjes." />
                                </div>
                            </flux:field>
                        </div>
                        @if($hasInventorySuggestion)
                            <div class="lg:col-span-5">
                                <x-ai-suggestion-panel
                                    :suggestion="$aiSuggestions['inventory']"
                                    field="inventory"
                                    :is-applied="$isInventoryApplied"
                                    :rawSuggestion="$aiSuggestions['inventory']"
                                />
                            </div>
                        @endif
                    </div>

                    <hr class="border-[var(--color-border-light)]">

                    @php
                        $hasProcessSuggestion = !empty($aiSuggestions['process']);
                        $isProcessApplied = in_array('process', $appliedSuggestions);
                    @endphp
                    <div class="grid grid-cols-1 @if($hasProcessSuggestion) lg:grid-cols-12 @endif gap-8">
                        <div class="@if($hasProcessSuggestion) lg:col-span-7 @endif">
                            <flux:field>
                                <flux:label class="text-base font-body font-bold">Werkwijze</flux:label>
                                <flux:description class="text-sm">Beschrijf stap voor stap hoe de activiteit verloopt.</flux:description>
                                <div
                                    x-data="{ expanded: @js(!empty($process)) }"
                                    @click="expanded = true"
                                    class="grid motion-safe:[transition:grid-template-rows_0.3s_cubic-bezier(0.25,1,0.5,1)]"
                                    :class="expanded ? 'grid-rows-[1fr] overflow-visible' : 'grid-rows-[100px] overflow-hidden cursor-text'"
                                >
                                    <flux:editor wire:model="process" toolbar="bold | bullet ordered | link" placeholder="bijv. 1. Verdeel de bingokaarten. 2. Speel het eerste fragment. 3. Laat bewoners het liedje raden..." />
                                </div>
                            </flux:field>
                        </div>
                        @if($hasProcessSuggestion)
                            <div class="lg:col-span-5">
                                <x-ai-suggestion-panel
                                    :suggestion="$aiSuggestions['process']"
                                    field="process"
                                    :is-applied="$isProcessApplied"
                                    :rawSuggestion="$aiSuggestions['process']"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </flux:tab.panel>

            {{-- Tab: Bestanden --}}
            <flux:tab.panel name="bestanden">
                <div class="space-y-6 pt-2">
                    @if(!empty($existingFiles))
                        <div>
                            <p class="text-sm font-medium text-[var(--color-text-secondary)] mb-3">Huidige bestanden</p>
                            <div class="flex flex-col gap-2">
                                @foreach($existingFiles as $file)
                                    <flux:file-item
                                        :heading="$file['name']"
                                        :size="$file['size']"
                                        wire:key="efile-{{ $file['id'] }}"
                                    >
                                        <x-slot name="actions">
                                            <flux:file-item.remove
                                                wire:click="removeFile({{ $file['id'] }})"
                                                wire:confirm="Weet je zeker dat je dit bestand wilt verwijderen?"
                                                aria-label="Verwijder {{ $file['name'] }}"
                                            />
                                        </x-slot>
                                    </flux:file-item>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <flux:field>
                        <div
                            x-data="{
                                uploadError: '',
                                uploadTip: '',
                                maxSize: 50 * 1024 * 1024,
                                allowedExtensions: ['pdf','pptx','docx','doc','ppt','jpg','jpeg','png'],
                                sizeTips: {
                                    pptx: 'Grote presentaties bevatten vaak foto\u2019s in hoge resolutie. Verklein ze in \u00e9\u00e9n keer: klik op een willekeurige foto \u2192 Afbeeldingen comprimeren \u2192 vink \u201cAlleen op deze afbeelding\u201d uit \u2192 kies \u201cE-mail (96 ppi)\u201d \u2192 sla opnieuw op.',
                                    ppt: 'Grote presentaties bevatten vaak foto\u2019s in hoge resolutie. Verklein ze in \u00e9\u00e9n keer: klik op een willekeurige foto \u2192 Afbeeldingen comprimeren \u2192 vink \u201cAlleen op deze afbeelding\u201d uit \u2192 kies \u201cE-mail (96 ppi)\u201d \u2192 sla opnieuw op.',
                                    docx: 'Grote Word-bestanden bevatten vaak foto\u2019s in hoge resolutie. Verklein ze in \u00e9\u00e9n keer: klik op een willekeurige foto \u2192 Afbeeldingen comprimeren \u2192 vink \u201cAlleen op deze afbeelding\u201d uit \u2192 kies \u201cE-mail (96 ppi)\u201d \u2192 sla opnieuw op.',
                                    doc: 'Grote Word-bestanden bevatten vaak foto\u2019s in hoge resolutie. Verklein ze in \u00e9\u00e9n keer: klik op een willekeurige foto \u2192 Afbeeldingen comprimeren \u2192 vink \u201cAlleen op deze afbeelding\u201d uit \u2192 kies \u201cE-mail (96 ppi)\u201d \u2192 sla opnieuw op.',
                                    pdf: 'Open het PDF in je browser, druk Ctrl+P en kies \u201cOpslaan als PDF\u201d of \u201cMicrosoft Print to PDF\u201d. Dit maakt vaak een veel kleiner bestand aan.',
                                    jpg: 'Open de foto in Paint \u2192 Formaat wijzigen \u2192 kies een kleiner percentage (bv. 50%) \u2192 sla op.',
                                    jpeg: 'Open de foto in Paint \u2192 Formaat wijzigen \u2192 kies een kleiner percentage (bv. 50%) \u2192 sla op.',
                                    png: 'Open de foto in Paint \u2192 Formaat wijzigen \u2192 kies een kleiner percentage (bv. 50%) \u2192 sla op als JPEG.',
                                },
                                validateFiles(files) {
                                    this.uploadError = '';
                                    this.uploadTip = '';
                                    for (const file of files) {
                                        const ext = file.name.split('.').pop().toLowerCase();
                                        if (file.size > this.maxSize) {
                                            this.uploadError = file.name + ' is te groot (' + Math.round(file.size / 1024 / 1024) + ' MB \u2014 max 50 MB). Probeer het bestand eerst te verkleinen.';
                                            this.uploadTip = this.sizeTips[ext] || '';
                                            return false;
                                        }
                                        if (!this.allowedExtensions.includes(ext)) {
                                            this.uploadError = file.name + ' kan niet worden ge\u00fcpload. Kies een PDF, PPTX, DOCX of afbeelding (JPG/PNG).';
                                            return false;
                                        }
                                    }
                                    return true;
                                }
                            }"
                            x-on:livewire-upload-error.window="uploadError = 'Het uploaden is mislukt. Controleer of het bestand kleiner is dan 50 MB en probeer het opnieuw.'"
                            x-init="
                                const self = this;
                                $nextTick(() => {
                                    const input = $el.querySelector('input[type=file]');
                                    if (input) {
                                        input.addEventListener('change', (e) => {
                                            if (!self.validateFiles(e.target.files)) {
                                                e.target.value = '';
                                                e.stopImmediatePropagation();
                                            }
                                        }, true);
                                    }
                                })
                            "
                        >
                            <flux:file-upload wire:model="newUploads" multiple accept=".pdf,.pptx,.docx,.doc,.ppt,.jpg,.jpeg,.png">
                                <flux:file-upload.dropzone
                                    heading="Sleep bestanden hierheen of klik om te bladeren"
                                    text="PDF, PPTX, DOCX, afbeeldingen — max 50MB per bestand"
                                    inline
                                />
                            </flux:file-upload>

                            {{-- Client-side error (Alpine) --}}
                            <div wire:ignore x-show="uploadError" x-cloak class="mt-3">
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:callout.heading><span x-text="uploadError"></span></flux:callout.heading>
                                    <flux:callout.text><p x-show="uploadTip" x-text="uploadTip"></p></flux:callout.text>
                                    <x-slot name="controls">
                                        <flux:button icon="x-mark" variant="ghost" x-on:click="uploadError = ''; uploadTip = ''" />
                                    </x-slot>
                                </flux:callout>
                            </div>

                            {{-- Server-side error (Livewire) --}}
                            @error('newUploads.*')
                                <div class="mt-3">
                                    <flux:callout variant="danger" icon="x-circle">
                                        <flux:callout.heading>{{ $message }}</flux:callout.heading>
                                    </flux:callout>
                                </div>
                            @enderror
                        </div>
                    </flux:field>
                </div>
            </flux:tab.panel>

            {{-- Tab: Details --}}
            <flux:tab.panel name="details">
                <div class="space-y-6 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label class="text-base font-body font-bold">Duur</flux:label>
                            <flux:description class="text-sm">Hoelang duurt het?</flux:description>
                            <flux:input wire:model="duration" class="text-base" placeholder="bijv. 30 min" />
                            @if(!empty($aiSuggestions['duration_estimate']))
                                <flux:description class="mt-1">
                                    <span class="text-xs text-[var(--color-primary)]">✨ Suggestie: {{ $aiSuggestions['duration_estimate'] }}</span>
                                </flux:description>
                            @endif
                        </flux:field>

                        <flux:field>
                            <flux:label class="text-base font-body font-bold">Groepsgrootte</flux:label>
                            <flux:description class="text-sm">Voor hoeveel personen?</flux:description>
                            <flux:input wire:model="groupSize" class="text-base" placeholder="bijv. 4-8" />
                            @if(!empty($aiSuggestions['group_size_estimate']))
                                <flux:description class="mt-1">
                                    <span class="text-xs text-[var(--color-primary)]">✨ Suggestie: {{ $aiSuggestions['group_size_estimate'] }}</span>
                                </flux:description>
                            @endif
                        </flux:field>
                    </div>

                    {{-- Theme tags (hidden from UI — suggestions still applied automatically) --}}

                    <hr class="border-[var(--color-border-light)]">

                    {{-- Initiative --}}
                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Gekoppeld initiatief</flux:label>
                        <flux:description class="text-sm">Optioneel — koppel deze fiche aan een initiatief.</flux:description>
                        <flux:select wire:model="selectedInitiativeId" variant="combobox" clearable placeholder="Zoek een initiatief...">
                            <flux:select.option value="">Geen initiatief</flux:select.option>
                            @foreach($allInitiatives as $initiative)
                                <flux:select.option :value="$initiative->id">{{ $initiative->title }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </flux:tab.panel>
        </flux:tab.group>

        {{-- Footer --}}
        <div
            x-data="{
                showNudge: false,
                nudgeConfirmed: false,
                get hasAi() {
                    return $wire.aiSuggestions !== null;
                },
                get descTooShort() {
                    const text = ($wire.description || '').replace(/<[^>]*>/g, '').trim();
                    return text.split(/\s+/).filter(w => w.length > 0).length < 15;
                },
                get contentMissing() {
                    const applied = $wire.appliedSuggestions || [];
                    const fields = ['preparation', 'inventory', 'process'];
                    const values = [$wire.preparation, $wire.inventory, $wire.process];
                    return fields.filter((f, i) =>
                        !applied.includes(f) && (values[i] || '').replace(/<[^>]*>/g, '').trim().length === 0
                    ).length >= 2;
                },
                get shouldNudge() {
                    return (this.descTooShort || this.contentMissing) && !this.nudgeConfirmed;
                }
            }"
            class="sticky bottom-0 z-10 bg-white border-t border-[var(--color-border-light)] space-y-3 pt-4 pb-4"
        >
            {{-- Nudge banner: shown when majority of optional content fields are empty --}}
            <div
                x-show="showNudge && shouldNudge"
                x-cloak
                role="alert"
                class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 rounded-xl bg-[var(--color-bg-accent-light)] border border-[var(--color-border-light)]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                </svg>
                <p class="flex-1 text-sm text-[var(--color-text-secondary)] min-w-[12rem]">
                    <strong class="font-semibold text-[var(--color-text-primary)]">Je fiche kan nog rijker.</strong>
                    <span x-show="descTooShort && contentMissing"> De beschrijving mag nog wat langer, en voorbereiding of werkwijze ontbreekt nog. Met een beetje extra uitleg kunnen collega's dit zo overnemen.</span>
                    <span x-show="descTooShort && !contentMissing"> De beschrijving mag nog wat langer — een paar zinnen extra helpen collega's meteen op weg.</span>
                    <span x-show="!descTooShort && contentMissing"> Met een voorbereiding of werkwijze wordt je fiche veel completer — dan kunnen collega's er meteen mee aan de slag.</span>
                </p>
                <button
                    type="button"
                    x-on:click="nudgeConfirmed = true; showNudge = false;"
                    class="shrink-0 h-8 px-3 rounded-full bg-[var(--color-primary)] text-white text-sm font-medium flex items-center hover:opacity-90 transition-opacity"
                >Negeer</button>
            </div>

            <div class="flex items-center justify-between" x-bind:class="showNudge && shouldNudge ? 'opacity-40 pointer-events-none select-none' : ''"
>
                @if($fiche->published && $fiche->initiative)
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                        Annuleren
                    </flux:button>
                @else
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('my-fiches.index') }}">
                        Annuleren
                    </flux:button>
                @endif

                @if($fiche->published)
                    <div class="flex items-center gap-3">
                        <flux:button variant="ghost" wire:click="saveDraft">
                            Maak concept
                        </flux:button>
                        <flux:button
                            variant="primary"
                            icon="check"
                            x-on:click="
                                if (shouldNudge) {
                                    showNudge = true;
                                } else {
                                    $wire.save();
                                }
                            "
                        >
                            Opslaan
                        </flux:button>
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <flux:button
                            variant="ghost"
                            x-on:click="
                                if (shouldNudge) {
                                    showNudge = true;
                                } else {
                                    $wire.saveDraft();
                                }
                            "
                        >
                            Opslaan als concept
                        </flux:button>
                        <flux:button
                            variant="primary"
                            icon="rocket-launch"
                            x-on:click="
                                if (shouldNudge) {
                                    showNudge = true;
                                } else {
                                    $wire.publish();
                                }
                            "
                        >
                            Publiceer
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
