<div>
    <div class="space-y-8">

        {{-- Top zone: Title + Description (always visible) --}}
        <div class="bg-white rounded-xl border border-[var(--color-border-light)] shadow-[var(--shadow-card)] p-6 space-y-6">
            <flux:field>
                <flux:label class="text-base font-body font-bold">Titel</flux:label>
                <flux:description class="text-sm">Wees specifiek — wat maakt jouw activiteit uniek of bijzonder?</flux:description>
                <flux:input wire:model="title" class="text-base" placeholder="bijv. Muziekbingo met schlagers uit de jaren '60" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label class="text-base font-body font-bold">Beschrijving</flux:label>
                <flux:description class="text-sm">Wat is je bedoeling met deze activiteit? Voor wie is ze bedoeld?</flux:description>
                <flux:editor wire:model="description" toolbar="bold | bullet ordered | link" placeholder="bijv. Een interactieve quiz waarbij bewoners liedjes herkennen. Geschikt voor groepen van 8-15 personen." />
                <flux:error name="description" />
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
                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Voorbereiding</flux:label>
                        <flux:description class="text-sm">Wat moet er klaargezet of voorbereid worden?</flux:description>
                        <flux:editor wire:model="preparation" toolbar="bold | bullet ordered | link" placeholder="bijv. Print de bingokaarten uit en test het geluid van de muziekinstallatie." />
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Benodigdheden</flux:label>
                        <flux:description class="text-sm">Welke materialen heb je nodig?</flux:description>
                        <flux:editor wire:model="inventory" toolbar="bold | bullet ordered | link" placeholder="bijv. Bingokaarten, stiften, muziekinstallatie, prijsjes." />
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-base font-body font-bold">Werkwijze</flux:label>
                        <flux:description class="text-sm">Beschrijf stap voor stap hoe de activiteit verloopt.</flux:description>
                        <flux:editor wire:model="process" toolbar="bold | bullet ordered | link" placeholder="bijv. 1. Verdeel de bingokaarten. 2. Speel het eerste fragment. 3. Laat bewoners het liedje raden..." />
                    </flux:field>
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
                        </flux:field>

                        <flux:field>
                            <flux:label class="text-base font-body font-bold">Groepsgrootte</flux:label>
                            <flux:description class="text-sm">Voor hoeveel personen?</flux:description>
                            <flux:input wire:model="groupSize" class="text-base" placeholder="bijv. 4-8" />
                        </flux:field>
                    </div>

                    <hr class="border-[var(--color-border-light)]">

                    {{-- Goal tags --}}
                    @feature('diamant-goals')
                    <flux:field>
                        <flux:label class="text-base font-body font-bold">DIAMANT-doelen</flux:label>
                        <flux:description class="text-sm">Welke doelen van het DIAMANT-model worden aangesproken?</flux:description>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($allGoalTags as $tag)
                                @php
                                    $facetSlug = str_replace('doel-', '', $tag->slug);
                                    $facet = config("diamant.facets.{$facetSlug}");
                                @endphp
                                <label wire:key="goal-{{ $tag->id }}" @class([
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors border',
                                    'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' => in_array($tag->id, $selectedGoalTags),
                                    'bg-white border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] text-[var(--color-text-primary)]' => !in_array($tag->id, $selectedGoalTags),
                                ])>
                                    <input type="checkbox" wire:model.live="selectedGoalTags" value="{{ $tag->id }}" class="sr-only">
                                    @if($facet)
                                        <x-diamant-gem :letter="$facet['letter']" size="xxs"
                                            :inverted="in_array($tag->id, $selectedGoalTags)" />
                                    @endif
                                    {{ $tag->name }}
                                </label>
                            @endforeach
                        </div>
                    </flux:field>
                    @endfeature

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
        <div class="flex items-center justify-between pt-2">
            @if($fiche->initiative)
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                    Annuleren
                </flux:button>
            @else
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('home') }}">
                    Annuleren
                </flux:button>
            @endif

            <flux:button variant="primary" icon="check" wire:click="save">
                Opslaan
            </flux:button>
        </div>
    </div>
</div>
