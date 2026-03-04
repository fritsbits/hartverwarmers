<div
    x-data="{
        storageKey: 'fiche-wizard-draft',
        hasDraft: false,
        init() {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                try {
                    const draft = JSON.parse(saved);
                    if (draft.title || draft.description || (draft.fileIds && draft.fileIds.length)) {
                        this.hasDraft = true;
                    }
                } catch (e) {
                    localStorage.removeItem(this.storageKey);
                }
            }
            this.startAutoSave();
        },
        saveDraft() {
            const draft = {
                title: $wire.title,
                description: $wire.description,
                preparation: $wire.preparation,
                inventory: $wire.inventory,
                process: $wire.process,
                materialsText: $wire.materialsText,
                duration: $wire.duration,
                groupSize: $wire.groupSize,
                selectedThemeTags: $wire.selectedThemeTags,
                selectedGoalTags: $wire.selectedGoalTags,
                selectedInitiativeId: $wire.selectedInitiativeId,
                currentStep: $wire.currentStep,
                fileIds: ($wire.uploadedFiles || []).map(f => f.id),
                previewFileId: $wire.previewFileId,
                savedAt: Date.now(),
            };
            if (draft.title || draft.description || draft.fileIds.length) {
                localStorage.setItem(this.storageKey, JSON.stringify(draft));
            }
        },
        restoreDraft() {
            const saved = localStorage.getItem(this.storageKey);
            if (!saved) return;
            try {
                const draft = JSON.parse(saved);
                $wire.title = draft.title || '';
                $wire.description = draft.description || '';
                $wire.preparation = draft.preparation || '';
                $wire.inventory = draft.inventory || '';
                $wire.process = draft.process || '';
                $wire.materialsText = draft.materialsText || '';
                $wire.duration = draft.duration || '';
                $wire.groupSize = draft.groupSize || '';
                $wire.selectedThemeTags = draft.selectedThemeTags || [];
                $wire.selectedGoalTags = draft.selectedGoalTags || [];
                $wire.selectedInitiativeId = draft.selectedInitiativeId || null;
                if (draft.fileIds && draft.fileIds.length) {
                    $wire.restoreUploadedFiles(draft.fileIds, draft.previewFileId || draft.mainFileId || null);
                }
            } catch (e) {
                console.error('Failed to restore draft:', e);
            }
            this.hasDraft = false;
        },
        dismissDraft() {
            localStorage.removeItem(this.storageKey);
            this.hasDraft = false;
        },
        startAutoSave() {
            setInterval(() => this.saveDraft(), 30000);
            window.addEventListener('beforeunload', () => this.saveDraft());
        },
    }"
    @fiche-saved.window="localStorage.removeItem(storageKey)"
>
    {{-- Restore draft banner --}}
    <template x-if="hasDraft">
        <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm text-amber-800">Er is een eerder ingevuld concept gevonden. Wil je dit herstellen?</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <flux:button variant="primary" size="sm" x-on:click="restoreDraft()">Herstellen</flux:button>
                <flux:button variant="ghost" size="sm" x-on:click="dismissDraft()">Negeren</flux:button>
            </div>
        </div>
    </template>

    {{-- Progress bar --}}
    <div class="mb-10">
        <div class="flex items-center justify-between">
            @foreach([1 => 'Bestanden', 2 => 'Details', 3 => 'Inhoud'] as $step => $label)
                <div class="flex items-center {{ $step < 3 ? 'flex-1' : '' }}">
                    <button
                        wire:click="goToStep({{ $step }})"
                        @class([
                            'flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold transition-colors shrink-0',
                            'bg-[var(--color-primary)] text-white' => $currentStep === $step,
                            'bg-[var(--color-primary)]/20 text-[var(--color-primary)]' => $currentStep > $step,
                            'bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)]' => $currentStep < $step,
                            'cursor-pointer' => $currentStep > $step,
                            'cursor-default' => $currentStep <= $step,
                        ])
                        @disabled($currentStep <= $step)
                    >
                        @if($currentStep > $step)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @else
                            {{ $step }}
                        @endif
                    </button>
                    <span @class([
                        'ml-2 text-sm font-medium whitespace-nowrap',
                        'text-[var(--color-primary)]' => $currentStep >= $step,
                        'text-[var(--color-text-secondary)]' => $currentStep < $step,
                    ])>{{ $label }}</span>
                    @if($step < 3)
                        <div @class([
                            'flex-1 h-0.5 mx-4 rounded',
                            'bg-[var(--color-primary)]/30' => $currentStep > $step,
                            'bg-[var(--color-border-light)]' => $currentStep <= $step,
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Two-column grid: main + sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8" @if(!$processingComplete && $processingStep !== 'idle') wire:poll.2s="checkProcessing" @endif>
        {{-- Main content area --}}
        <div class="min-w-0">

            {{-- ============================================== --}}
            {{-- Step 1: Bestanden                              --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 1" x-cloak>
                <h2 class="text-3xl mb-2">Upload je bestanden</h2>
                <p class="text-[var(--color-text-secondary)] mb-8">Upload je bestanden — we analyseren de tekst van alle bestanden met AI.</p>

                <div class="space-y-6">
                    <flux:field>
                        <flux:label class="text-base font-heading font-bold">Bestanden</flux:label>

                        <flux:file-upload wire:model="uploads" multiple>
                            <flux:file-upload.dropzone
                                heading="Sleep bestanden hierheen of klik om te bladeren"
                                text="PDF, PPTX, DOCX, afbeeldingen — max 50MB per bestand"
                                with-progress
                            />
                        </flux:file-upload>
                        <flux:error name="uploads" />
                        <flux:error name="uploads.*" />

                        @if(!empty($uploadedFiles))
                            <div class="mt-3 flex flex-col gap-2">
                                @foreach($uploadedFiles as $file)
                                    <flux:file-item
                                        :heading="$file['name']"
                                        :size="$file['size']"
                                        wire:key="file-{{ $file['id'] }}"
                                    >
                                        <x-slot name="actions">
                                            @if($file['id'] === $previewFileId)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--color-primary)]/10 text-[var(--color-primary)]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Preview
                                                </span>
                                            @endif
                                            <flux:file-item.remove
                                                wire:click="removeFile({{ $file['id'] }})"
                                                aria-label="Verwijder {{ $file['name'] }}"
                                            />
                                        </x-slot>
                                    </flux:file-item>
                                @endforeach
                            </div>
                        @endif
                    </flux:field>
                </div>

                {{-- Preview file picker modal --}}
                <flux:modal wire:model.self="showPreviewFileModal" :dismissible="false" :closable="false" class="md:w-96">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Kies een voorbeeldbestand</flux:heading>
                            <flux:text class="mt-2">Welk bestand wil je als visuele preview tonen op de fiche-pagina?</flux:text>
                        </div>

                        <flux:radio.group wire:model="previewFileId" variant="cards" class="flex-col">
                            @foreach($uploadedFiles as $file)
                                <flux:radio :value="$file['id']" :label="$file['name']" :description="$file['type']" />
                            @endforeach
                        </flux:radio.group>

                        <div class="flex justify-end">
                            <flux:button variant="primary" wire:click="confirmPreviewFile">Bevestigen</flux:button>
                        </div>
                    </div>
                </flux:modal>

                <div class="flex justify-end mt-8">
                    <flux:button variant="primary" wire:click="submitStep1" icon-trailing="arrow-right">
                        {{ empty($uploadedFiles) ? 'Verder zonder bestand' : 'Volgende' }}
                    </flux:button>
                </div>
            </div>

            {{-- ============================================== --}}
            {{-- Step 2: Details                                --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 2" x-cloak>
                {{-- Mobile processing banner --}}
                @if($processingStep !== 'idle' && $processingStep !== 'done' && $processingStep !== 'failed' && $processingStep !== 'skipped' && !$processingComplete)
                    <div class="lg:hidden mb-6 p-3 rounded-xl border bg-[var(--color-primary)]/5 border-[var(--color-primary)]/20">
                        <div class="flex items-center gap-2 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)] animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" />
                            </svg>
                            <span class="text-[var(--color-primary)] font-medium">
                                @if($processingStep === 'extracting') Tekst uitlezen...
                                @elseif($processingStep === 'analyzing') Suggesties genereren...
                                @endif
                            </span>
                            @if($processingStale)
                                <flux:button size="xs" variant="ghost" wire:click="skipProcessing" class="ml-auto">Overslaan</flux:button>
                            @endif
                        </div>
                    </div>
                @elseif($processingComplete && $processingStep === 'done')
                    <div class="lg:hidden mb-6 p-3 rounded-xl border bg-green-50 border-green-200">
                        <div class="flex items-center gap-2 text-sm text-green-700 font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Analyse voltooid — suggesties staan klaar!
                        </div>
                    </div>
                @endif

                <h2 class="text-3xl mb-2">Details</h2>
                <p class="text-[var(--color-text-secondary)] mb-8">Vul de kerngegevens in terwijl we je bestanden analyseren.</p>

                <div class="space-y-6">
                    {{-- Title --}}
                    <flux:field>
                        <flux:label class="text-base font-heading font-bold">Titel <span class="field-tag ml-1">Verplicht</span></flux:label>
                        <flux:description>Wees specifiek — wat maakt jouw activiteit uniek of bijzonder?</flux:description>
                        <flux:input wire:model="title" placeholder="bijv. Muziekbingo met schlagers uit de jaren '60" />
                        <flux:error name="title" />
                    </flux:field>

                    {{-- Duration & Group size --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Duur</flux:label>
                            <flux:input wire:model="duration" placeholder="bijv. 30 min" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Groepsgrootte</flux:label>
                            <flux:input wire:model="groupSize" placeholder="bijv. 4-8" />
                        </flux:field>
                    </div>

                    <hr class="border-[var(--color-border-light)]">

                    {{-- DIAMANT goals --}}
                    @feature('diamant-goals')
                    <flux:field>
                        <flux:label class="text-base font-heading font-bold">DIAMANT-doelen</flux:label>
                        <flux:description>Welke doelen van het DIAMANT-model worden aangesproken?</flux:description>

                        @if(!empty($suggestedGoalTagIds))
                            <div class="text-xs font-semibold text-[var(--color-primary)] mt-2 mb-1 uppercase tracking-wider">Aanbevolen</div>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($allGoalTags as $tag)
                                @php
                                    $isSuggested = in_array($tag->id, $suggestedGoalTagIds);
                                    $facetSlug = str_replace('doel-', '', $tag->slug);
                                    $facet = config("diamant.facets.{$facetSlug}");
                                @endphp

                                @if($isSuggested)
                                    <label wire:key="goal-{{ $tag->id }}" @class([
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors border',
                                        'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' => in_array($tag->id, $selectedGoalTags),
                                        'bg-[var(--color-primary)]/10 border-[var(--color-primary)]/30 hover:border-[var(--color-primary)] text-[var(--color-primary)]' => !in_array($tag->id, $selectedGoalTags),
                                    ])>
                                        <input type="checkbox" wire:model.live="selectedGoalTags" value="{{ $tag->id }}" class="sr-only">
                                        @if($facet)
                                            <x-diamant-gem :letter="$facet['letter']" size="xxs"
                                                :inverted="in_array($tag->id, $selectedGoalTags)" />
                                        @endif
                                        {{ $tag->name }}
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        @if(!empty($suggestedGoalTagIds))
                            <button wire:click="$toggle('showMoreGoalTags')" class="mt-2 text-xs text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] underline">
                                {{ $showMoreGoalTags ? 'Minder tonen' : 'Alle doelen tonen' }}
                            </button>
                        @endif

                        @if($showMoreGoalTags || empty($suggestedGoalTagIds))
                            @if(!empty($suggestedGoalTagIds))
                                <div class="text-xs font-semibold text-[var(--color-text-secondary)] mt-3 mb-1 uppercase tracking-wider">Overige doelen</div>
                            @endif
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($allGoalTags as $tag)
                                    @php
                                        $facetSlug = str_replace('doel-', '', $tag->slug);
                                        $facet = config("diamant.facets.{$facetSlug}");
                                    @endphp
                                    @if(!in_array($tag->id, $suggestedGoalTagIds))
                                        <label wire:key="goal-more-{{ $tag->id }}" @class([
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
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </flux:field>
                    @endfeature

                    {{-- Theme tags --}}
                    <flux:field>
                        <flux:label class="text-base font-heading font-bold">Thema's</flux:label>
                        <flux:description>Selecteer de thema's die bij deze activiteit passen.</flux:description>

                        @if(!empty($suggestedThemeTagIds))
                            <div class="text-xs font-semibold text-[var(--color-primary)] mt-2 mb-1 uppercase tracking-wider">Aanbevolen</div>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($allThemeTags as $tag)
                                @php $isSuggested = in_array($tag->id, $suggestedThemeTagIds); @endphp

                                @if($isSuggested)
                                    <label wire:key="theme-{{ $tag->id }}" @class([
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors border',
                                        'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' => in_array($tag->id, $selectedThemeTags),
                                        'bg-[var(--color-primary)]/10 border-[var(--color-primary)]/30 hover:border-[var(--color-primary)] text-[var(--color-primary)]' => !in_array($tag->id, $selectedThemeTags),
                                    ])>
                                        <input type="checkbox" wire:model.live="selectedThemeTags" value="{{ $tag->id }}" class="sr-only">
                                        {{ $tag->name }}
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        @if(!empty($suggestedThemeTagIds))
                            <button wire:click="$toggle('showMoreThemeTags')" class="mt-2 text-xs text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] underline">
                                {{ $showMoreThemeTags ? 'Minder tonen' : 'Alle thema\'s tonen' }}
                            </button>
                        @endif

                        @if($showMoreThemeTags || empty($suggestedThemeTagIds))
                            @if(!empty($suggestedThemeTagIds))
                                <div class="text-xs font-semibold text-[var(--color-text-secondary)] mt-3 mb-1 uppercase tracking-wider">Overige thema's</div>
                            @endif
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($allThemeTags as $tag)
                                    @if(!in_array($tag->id, $suggestedThemeTagIds))
                                        <label wire:key="theme-more-{{ $tag->id }}" @class([
                                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors border',
                                            'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' => in_array($tag->id, $selectedThemeTags),
                                            'bg-white border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] text-[var(--color-text-secondary)]' => !in_array($tag->id, $selectedThemeTags),
                                        ])>
                                            <input type="checkbox" wire:model.live="selectedThemeTags" value="{{ $tag->id }}" class="sr-only">
                                            {{ $tag->name }}
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </flux:field>

                    <hr class="border-[var(--color-border-light)]">

                    {{-- Initiative linking --}}
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label class="text-base font-heading font-bold">Gekoppeld initiatief</flux:label>
                            <flux:description>Optioneel — koppel deze fiche aan een initiatief.</flux:description>

                            {{-- AI-matched initiatives --}}
                            @if(!empty($matchedInitiatives))
                                <div class="mt-3 mb-4">
                                    <div class="text-xs font-semibold text-[var(--color-primary)] mb-2 uppercase tracking-wider flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        Voorgesteld door AI
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($matchedInitiatives as $match)
                                            <label wire:key="match-{{ $match['id'] }}" class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all hover:-translate-y-0.5 {{ $selectedInitiativeId === $match['id'] ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/5 shadow-sm' : 'border-[var(--color-border-light)] hover:border-[var(--color-border-hover)]' }}">
                                                <input type="radio" wire:model.live="selectedInitiativeId" value="{{ $match['id'] }}" class="mt-1 accent-[var(--color-primary)]">
                                                <div>
                                                    <div class="font-heading font-bold text-sm">{{ $match['title'] }}</div>
                                                    @if($match['reason'])
                                                        <p class="text-xs text-[var(--color-text-secondary)] mt-0.5">{{ $match['reason'] }}</p>
                                                    @endif
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <flux:select wire:model.live="selectedInitiativeId" placeholder="Selecteer een initiatief...">
                                <flux:select.option value="">Geen initiatief</flux:select.option>
                                @foreach($allInitiatives as $initiative)
                                    <flux:select.option :value="$initiative->id">{{ $initiative->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                </div>

                <div class="flex justify-between mt-8">
                    <flux:button variant="ghost" wire:click="goToStep(1)" icon="arrow-left">
                        Vorige
                    </flux:button>
                    <flux:button variant="primary" wire:click="submitStep2" icon-trailing="arrow-right">
                        Volgende
                    </flux:button>
                </div>
            </div>

            {{-- ============================================== --}}
            {{-- Step 3: Inhoud                                 --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 3" x-cloak>
                <h2 class="text-3xl mb-2">Inhoud</h2>
                <p class="text-[var(--color-text-secondary)] mb-8">Schrijf de inhoud van je fiche, of neem de AI-suggesties over.</p>

                {{-- AI still processing banner --}}
                @if(!$processingComplete && $processingStep !== 'idle' && $processingStep !== 'failed' && $processingStep !== 'skipped')
                    <div class="mb-6 p-3 rounded-xl border bg-[var(--color-primary)]/5 border-[var(--color-primary)]/20">
                        <div class="flex items-center gap-2 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)] animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" />
                            </svg>
                            <span class="text-[var(--color-primary)] font-medium">AI-suggesties worden gegenereerd...</span>
                            @if($processingStale)
                                <flux:button size="xs" variant="ghost" wire:click="skipProcessing" class="ml-auto">Overslaan</flux:button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="space-y-8">
                    @foreach($contentFields as $index => $field)
                        @php
                            $hasAiSuggestion = $this->{$field['aiProp']} !== null && !in_array($field['field'], $dismissedSuggestions);
                        @endphp

                        <div wire:key="content-{{ $field['field'] }}">
                            <h4 class="font-heading font-bold text-base mb-1">{{ $field['label'] }}</h4>
                            <p class="text-sm text-[var(--color-text-secondary)] mb-3">{{ $field['description'] }}</p>

                            <div class="grid grid-cols-1 {{ $hasAiSuggestion ? 'md:grid-cols-2' : '' }} gap-4">
                                {{-- User's editable field --}}
                                <div>
                                    @if($hasAiSuggestion)
                                        <div class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-secondary)] mb-2">Jouw versie</div>
                                    @endif
                                    <flux:textarea
                                        wire:model="{{ $field['userProp'] }}"
                                        rows="{{ $field['rows'] }}"
                                        placeholder="{{ $field['placeholder'] }}"
                                        class="text-sm"
                                    />
                                </div>

                                {{-- AI suggestion --}}
                                @if($hasAiSuggestion)
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wider text-[var(--color-primary)] mb-2 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                            </svg>
                                            AI-suggestie
                                        </div>
                                        <div class="p-4 rounded-lg border-2 border-dashed border-[var(--color-primary)]/30 bg-[var(--color-bg-cream)] text-sm">
                                            <div class="prose prose-sm max-w-none">{!! $this->{$field['aiProp']} !!}</div>
                                            <div class="flex gap-2 mt-3">
                                                <flux:button size="xs" variant="primary" wire:click="applySuggestion('{{ $field['field'] }}')">
                                                    Overnemen
                                                </flux:button>
                                                <flux:button size="xs" variant="ghost" wire:click="dismissSuggestion('{{ $field['field'] }}')">
                                                    Negeren
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>

                @error('save')
                    <div class="mt-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                        </svg>
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    </div>
                @enderror

                <div class="flex justify-between mt-8 pt-6 border-t border-[var(--color-border-light)]">
                    <flux:button variant="ghost" wire:click="goToStep(2)" icon="arrow-left">
                        Vorige
                    </flux:button>
                    <div class="flex gap-3">
                        <flux:button variant="ghost" wire:click="saveDraft">
                            Opslaan als concept
                        </flux:button>
                        <flux:button variant="primary" wire:click="publish" icon="rocket-launch">
                            Publiceer
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Sidebar (desktop only) --}}
        <div class="hidden lg:block">
            <div class="sticky top-24">
                <div class="rounded-xl border border-[var(--color-border-light)] bg-white p-5">
                    {{-- Idle — no file yet --}}
                    @if($processingStep === 'idle')
                        <div class="flex items-center gap-2 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <h3 class="font-heading font-bold text-base">AI-assistent</h3>
                        </div>
                        <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                            Na het uploaden analyseren we je bestanden automatisch. We herkennen tekst, genereren previews en doen een AI-analyse om je fiche te verrijken met suggesties.
                        </p>

                    {{-- Processing in progress --}}
                    @elseif(!$processingComplete)
                        <div class="flex items-center gap-2 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                            <h3 class="font-heading font-bold text-base">Verwerking</h3>
                        </div>

                        @php
                            $sidebarSteps = [
                                'upload' => ['label' => 'Upload', 'done' => true],
                                'extracting' => ['label' => 'Tekst uitlezen', 'done' => in_array($processingStep, ['analyzing', 'done', 'failed']), 'active' => $processingStep === 'extracting'],
                                'suggesting' => ['label' => 'Suggesties genereren', 'done' => in_array($processingStep, ['done', 'failed']), 'active' => $processingStep === 'analyzing'],
                            ];
                        @endphp

                        <div class="space-y-3">
                            @foreach($sidebarSteps as $key => $stepInfo)
                                <div class="flex items-center gap-3" wire:key="sidebar-{{ $key }}">
                                    @if($stepInfo['done'] ?? false)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif($stepInfo['active'] ?? false)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)] animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" />
                                        </svg>
                                    @else
                                        <div class="w-5 h-5 rounded-full border-2 border-[var(--color-border-light)] shrink-0"></div>
                                    @endif
                                    <span @class([
                                        'text-sm font-medium',
                                        'text-green-700' => $stepInfo['done'] ?? false,
                                        'text-[var(--color-primary)]' => $stepInfo['active'] ?? false,
                                        'text-[var(--color-text-secondary)]' => !($stepInfo['done'] ?? false) && !($stepInfo['active'] ?? false),
                                    ])>{{ $stepInfo['label'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if($processingStale)
                            <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200">
                                <p class="text-xs text-amber-800 mb-2">Analyse duurt langer dan verwacht. Start de queue worker in een terminal met: <code class="bg-amber-100 px-1 rounded font-mono">composer run dev</code></p>
                                <flux:button size="xs" variant="ghost" wire:click="skipProcessing" class="w-full">
                                    Overslaan en doorgaan
                                </flux:button>
                            </div>
                        @endif

                    {{-- Done --}}
                    @elseif($processingComplete && $processingStep === 'done')
                        <div class="text-center py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="font-heading font-bold text-base text-green-700">Analyse voltooid!</h3>
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">Suggesties staan klaar bij de inhoudsvelden.</p>
                        </div>

                    {{-- Failed or skipped --}}
                    @elseif($processingComplete)
                        <div class="text-center py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-amber-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <h3 class="font-heading font-bold text-base text-amber-700">
                                {{ $processingStep === 'skipped' ? 'Analyse overgeslagen' : 'Analyse niet gelukt' }}
                            </h3>
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">Je kunt je fiche handmatig invullen.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
