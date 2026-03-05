<div>
    {{-- HERO (cream bg) --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-6">
            <span class="section-label section-label-hero">Nieuwe fiche</span>
            <div class="flex items-baseline gap-3">
                <h1 class="text-5xl mt-1">Nieuwe fiche toevoegen</h1>
                @if($devMode)
                    <span class="text-xs text-amber-600 font-medium whitespace-nowrap">DEV MODE</span>
                @endif
            </div>

            {{-- Progress bar --}}
            <div class="mt-2 bg-white rounded-2xl shadow-md px-4 py-3 relative z-10 translate-y-1/2">
                <div class="flex items-center justify-between">
                    @foreach([1 => ['Bestanden', 'opladen'], 2 => ['Kerngegevens', 'invullen'], 3 => ['Fiche', 'uitwerken'], 4 => ['Resultaat', 'bewonderen']] as $step => $label)
                        <div class="flex items-center {{ $step < 4 ? 'flex-1' : '' }}">
                            <button
                                wire:click="goToStep({{ $step }})"
                                @class([
                                    'group flex items-center gap-2',
                                    'cursor-pointer' => $currentStep > $step || ($devMode && $step <= 3),
                                    'cursor-default' => ($currentStep <= $step && !$devMode) || $step === 4,
                                ])
                                @disabled(($currentStep <= $step && !$devMode) || $step === 4)
                            >
                                <span @class([
                                    'flex items-center justify-center w-[30px] h-[30px] rounded-full text-sm font-bold transition-colors shrink-0',
                                    'bg-[var(--color-primary)] text-white' => $currentStep === $step,
                                    'bg-[var(--color-primary)]/20 text-[var(--color-primary)] group-hover:bg-[var(--color-primary)]/40' => $currentStep > $step,
                                    'bg-[var(--color-bg-subtle)] text-[var(--color-text-secondary)]' => $currentStep < $step,
                                ])>
                                    @if($currentStep > $step)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @elseif($step === 4 && $currentStep === 4)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                        </svg>
                                    @else
                                        {{ $step }}
                                    @endif
                                </span>
                                <span @class([
                                    'max-sm:hidden text-sm font-semibold leading-tight transition-colors',
                                    'text-[var(--color-primary)] group-hover:text-[var(--color-primary-hover)]' => $currentStep >= $step,
                                    'text-[var(--color-text-secondary)]' => $currentStep < $step,
                                ])>{{ $label[0] }}</span>
                            </button>
                            @if($step < 4)
                                <div class="flex-1 mx-4 h-0.5 rounded bg-[var(--color-border-light)] relative overflow-hidden">
                                    <div @class([
                                        'absolute inset-y-0 left-0 rounded bg-[var(--color-primary)]/30 transition-all duration-500 ease-out',
                                        'w-full' => $currentStep > $step,
                                        'w-0' => $currentStep <= $step,
                                    ])></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- FORM CONTENT (white bg) --}}
    <section class="overflow-x-hidden">
        <div class="wizard-form max-w-6xl mx-auto px-6 pt-16 pb-12" @if(!$processingComplete && $processingStep !== 'idle') wire:poll.2s="checkProcessing" @endif>

            {{-- ============================================== --}}
            {{-- Step 1: Bestanden                              --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 1" x-cloak class="wizard-step-illustrated">
                <img src="{{ asset('images/wizard/bestanden-uploaden.png') }}" alt="" aria-hidden="true" class="wizard-illustration hidden lg:block">
                <div class="relative mb-0">
                    <h2 class="relative z-10 text-3xl">Upload je bestanden</h2>
                </div>
                <p class="wizard-lead mb-8 relative z-10">Upload je bestanden — we analyseren de tekst en doen suggesties</p>

                <div class="space-y-10 relative z-10">
                    <flux:field>
                        <div @class([
                            'grid gap-6',
                            'grid-cols-1' => empty($uploadedFiles),
                            'grid-cols-1 lg:grid-cols-2' => !empty($uploadedFiles),
                        ])>
                            {{-- Left: drop zone --}}
                            <div>
                                <flux:file-upload wire:model="uploads" multiple>
                                    <flux:file-upload.dropzone
                                        heading="Sleep bestanden hierheen of klik om te bladeren"
                                        text="PDF, PPTX, DOCX, afbeeldingen — max 50MB per bestand"
                                        with-progress
                                    />
                                </flux:file-upload>
                                <flux:error name="uploads" />
                                <flux:error name="uploads.*" />
                            </div>

                            {{-- Right: file list --}}
                            @if(!empty($uploadedFiles))
                                <div>
                                    <flux:label>Bestanden</flux:label>
                                    <div class="mt-2 flex flex-col gap-2">
                                        @foreach($uploadedFiles as $file)
                                            <flux:file-item
                                                :heading="$file['name']"
                                                :size="$file['size']"
                                                wire:key="file-{{ $file['id'] }}"
                                            >
                                                <x-slot name="actions">
                                                    @if($file['id'] === $previewFileId)
                                                        <flux:tooltip position="top">
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--color-primary)]/10 text-[var(--color-primary)]">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                </svg>
                                                                Preview
                                                            </span>
                                                            <flux:tooltip.content>Dit bestand krijgt automatisch een voorvertoning.</flux:tooltip.content>
                                                        </flux:tooltip>
                                                    @endif
                                                    <flux:file-item.remove
                                                        wire:click="removeFile({{ $file['id'] }})"
                                                        aria-label="Verwijder {{ $file['name'] }}"
                                                    />
                                                </x-slot>
                                            </flux:file-item>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
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

            </div>

            {{-- ============================================== --}}
            {{-- Step 2: Details                                --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 2" x-cloak class="wizard-step-illustrated">
                <img src="{{ asset('images/wizard/kernideeen-verzamelen.png') }}" alt="" aria-hidden="true" class="wizard-illustration hidden lg:block">
                <div class="relative mb-0">
                    <h2 class="relative z-10 text-3xl">Kerngegevens</h2>
                </div>
                <p class="wizard-lead mb-8 relative z-10">Vul de kerngegevens in terwijl we je bestanden analyseren</p>

                <div class="space-y-10 relative z-10">
                    {{-- Title --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <div>
                            <flux:field>
                                <flux:label>Titel <span class="field-tag ml-1">Verplicht</span></flux:label>
                                <flux:description>Geef een specifieke titel aan je activiteit.</flux:description>
                                <flux:input wire:model.live.debounce.500ms="title" placeholder="bijv. Muziekbingo met schlagers uit de jaren '60" />
                                <flux:error name="title" />
                            </flux:field>
                        </div>

                        @if(!empty($similarFiches))
                            <div>
                                <div class="similar-fiches-tip">
                                    <flux:icon.sparkles class="similar-fiches-tip-icon" />
                                    <div>
                                        @if($similarFiches['count'] === 1)
                                            <p>Er bestaat al <strong>1 {{ $similarFiches['keyword'] }}-fiche</strong>: {{ $similarFiches['examples'][0] }}.</p>
                                        @else
                                            <p>Er bestaan al <strong>{{ $similarFiches['count'] }} {{ $similarFiches['keyword'] }}-fiches</strong>, waaronder {{ implode(', ', array_slice($similarFiches['examples'], 0, -1)) }} en {{ end($similarFiches['examples']) }}.</p>
                                        @endif
                                        <p>Wat maakt jouw activiteit uniek?</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Duration & Group size --}}
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <flux:field>
                            <flux:label>Duur</flux:label>
                            <flux:input wire:model="duration" placeholder="bijv. 30 min" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Groepsgrootte</flux:label>
                            <flux:input wire:model="groupSize" placeholder="bijv. 4-8" />
                        </flux:field>
                    </div>

                    {{-- DIAMANT goals --}}
                    @feature('diamant-goals')
                    <flux:field>
                        <flux:label>DIAMANT-doelen</flux:label>
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

                    {{-- Theme tags (hidden from UI — suggestions still applied automatically) --}}

                    {{-- Initiative linking --}}
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Gekoppeld initiatief</flux:label>
                            <flux:description>Optioneel — koppel deze fiche aan een initiatief</flux:description>

                            {{-- Inline processing indicator --}}
                            @if(!$processingComplete && $processingStep !== 'idle' && $processingStep !== 'skipped')
                                @php
                                    $step2InlineSteps = [
                                        ['label' => 'Upload', 'done' => true, 'active' => false],
                                        ['label' => 'Tekst uitlezen', 'done' => in_array($processingStep, ['analyzing', 'done', 'failed']), 'active' => $processingStep === 'extracting'],
                                        ['label' => 'Suggesties', 'done' => in_array($processingStep, ['done', 'failed']), 'active' => $processingStep === 'analyzing'],
                                    ];
                                @endphp
                                <div class="mt-3 mb-4 flex items-center gap-3 text-sm text-[var(--color-text-secondary)]">
                                    @foreach($step2InlineSteps as $stepInfo)
                                        <span class="flex items-center gap-1.5 {{ $stepInfo['done'] ? 'text-[var(--color-primary)]' : ($stepInfo['active'] ? 'text-[var(--color-text-secondary)]' : 'text-[var(--color-text-secondary)]/50') }}">
                                            @if($stepInfo['done'])
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="M7.5 12.5L10.5 15.5L16.5 9.5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            @elseif($stepInfo['active'])
                                                <svg class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" /></svg>
                                            @else
                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/></svg>
                                            @endif
                                            {{ $stepInfo['label'] }}
                                        </span>
                                    @endforeach
                                    @if($processingStale)
                                        <button wire:click="skipProcessing" class="text-xs underline text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">Overslaan</button>
                                    @endif
                                </div>
                            @endif

                            {{-- Matched initiatives --}}
                            @if(!empty($matchedInitiatives))
                                <div class="mt-3 mb-4">
                                    <div class="text-xs font-semibold text-[var(--color-primary)] mb-2 uppercase tracking-wider flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        Voorgesteld
                                    </div>
                                    <flux:radio.group wire:model.live="selectedInitiativeId" variant="cards" class="initiative-cards max-sm:flex-col">
                                        @foreach($matchedInitiatives as $match)
                                            <flux:radio wire:key="match-{{ $match['id'] }}" :value="$match['id']" :label="$match['title']" :description="$match['reason']" />
                                        @endforeach
                                    </flux:radio.group>
                                </div>
                            @endif

                            {{-- Initiative dropdown hidden — suggestions should suffice --}}
                        </flux:field>
                    </div>
                </div>

            </div>

            {{-- ============================================== --}}
            {{-- Step 3: Inhoud                                 --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 3" x-cloak class="wizard-step-illustrated">
                <img src="{{ asset('images/wizard/schrijven-en-componeren.png') }}" alt="" aria-hidden="true" class="wizard-illustration hidden lg:block">
                <div class="relative mb-0">
                    <h2 class="relative z-10 text-3xl">Fiche uitwerken</h2>
                </div>
                <p class="wizard-lead mb-8 relative z-10">Schrijf de inhoud van je fiche, of neem de suggesties over</p>

                {{-- Still processing banner --}}
                @if(!$processingComplete && $processingStep !== 'idle' && $processingStep !== 'failed' && $processingStep !== 'skipped')
                    <div class="mb-6 p-3 rounded-xl border bg-[var(--color-primary)]/5 border-[var(--color-primary)]/20">
                        <div class="flex items-center gap-2 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)] animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" />
                            </svg>
                            <span class="text-[var(--color-primary)] font-medium">Suggesties worden gegenereerd...</span>
                            @if($processingStale)
                                <flux:button size="xs" variant="ghost" wire:click="skipProcessing" class="ml-auto">Overslaan</flux:button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="space-y-9">
                    @foreach($contentFields as $index => $field)
                        @php
                            $hasAiSuggestion = $this->{$field['aiProp']} !== null && !in_array($field['field'], $dismissedSuggestions);
                            $isApplied = in_array($field['field'], $appliedSuggestions);
                        @endphp

                        <div wire:key="content-{{ $field['field'] }}">
                            <flux:label>{{ $field['label'] }} @if($field['required'] ?? false)<span class="field-tag ml-1">Verplicht</span>@endif</flux:label>
                            <flux:description>{{ $field['description'] }}</flux:description>

                            <div class="grid grid-cols-1 {{ $hasAiSuggestion ? 'lg:grid-cols-12' : '' }} gap-8">
                                {{-- User's editable field --}}
                                <div class="{{ $hasAiSuggestion ? 'lg:col-span-8' : '' }}">
                                    <flux:editor
                                        wire:model="{{ $field['userProp'] }}"
                                        toolbar="bold | bullet ordered | link"
                                        placeholder="{{ $field['placeholder'] }}"
                                    />
                                </div>

                                {{-- Suggestion --}}
                                @if($hasAiSuggestion)
                                    <div class="lg:col-span-4 flex gap-2.5 bg-white py-4 pl-2 pr-4 text-sm text-[var(--color-text-primary)]/70">
                                        <flux:icon.sparkles class="w-5 h-5 shrink-0 text-[var(--color-primary)] mt-0.5" />
                                        <div class="min-w-0">
                                        <div class="text-xs font-semibold text-[var(--color-text-secondary)] mb-3 uppercase tracking-wider">Suggestie</div>
                                        <div class="prose prose-sm max-w-none [&_strong]:text-[var(--color-text-primary)]/80">{!! $this->{$field['aiProp']} !!}</div>
                                        <div class="mt-3">
                                            @if($isApplied)
                                                <span class="inline-flex items-center gap-1 h-7 px-2 text-xs font-medium text-[var(--color-text-secondary)]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                    Toegevoegd
                                                </span>
                                            @else
                                                <flux:button size="xs" variant="filled" x-on:click="let y = window.scrollY; $wire.applySuggestion('{{ $field['field'] }}').then(() => { requestAnimationFrame(() => window.scrollTo(0, y)) })">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                                    </svg>
                                                    Toevoegen
                                                </flux:button>
                                            @endif
                                        </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <flux:error :name="$field['field']" />
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

            </div>

            {{-- ============================================== --}}
            {{-- Step 4: Resultaat bewonderen (celebration)     --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 4" x-cloak class="wizard-step-illustrated"
                x-init="$watch('$wire.currentStep', value => {
                    if (value === 4 && $wire.publishedFicheUrl) {
                        setTimeout(() => window.location.href = $wire.publishedFicheUrl, 5000);
                    }
                })"
            >
                <img src="{{ asset('images/wizard/resultaten-bekijken.png') }}" alt="" aria-hidden="true" class="wizard-illustration hidden lg:block">
                <div class="relative mb-2">
                    <h2 class="relative z-10 text-3xl">Resultaat bewonderen</h2>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] px-8 py-16 mt-8 text-center">
                    {{-- Confetti --}}
                    <div class="wizard-confetti" aria-hidden="true">
                        @for($i = 1; $i <= 20; $i++)
                            <span class="wizard-confetti-particle"></span>
                        @endfor
                    </div>

                    {{-- Heart icon --}}
                    <div class="relative mx-auto mb-6 w-24 h-24 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full bg-[var(--color-primary)]/10 animate-pulse"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-[var(--color-primary)]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                        </svg>
                    </div>

                    <h3 class="text-3xl mb-3">Prachtig werk!</h3>
                    <p class="text-lg text-[var(--color-text-secondary)] font-light max-w-md mx-auto mb-8">
                        Je fiche is gepubliceerd en staat klaar voor alle collega's. Bedankt voor je warme bijdrage!
                    </p>

                    <a href="{{ $publishedFicheUrl }}" class="btn-pill">
                        Bekijk je fiche &rarr;
                    </a>

                    <p class="mt-6 text-sm text-[var(--color-text-secondary)]/60">
                        Je wordt automatisch doorgestuurd...
                    </p>
                </div>
            </div>
        </div>

        {{-- Full-width cream footer band --}}
        <div class="wizard-form-footer" x-show="$wire.currentStep < 4" x-cloak>
            <div class="max-w-6xl mx-auto px-6 py-5">
                {{-- Step 1 footer --}}
                <div x-show="$wire.currentStep === 1" class="flex items-center justify-between">
                    {{-- Inline processing indicator --}}
                    <div>
                        @if(!$processingComplete && $processingStep !== 'idle' && $processingStep !== 'skipped')
                            @php
                                $inlineSteps = [
                                    ['label' => 'Upload', 'done' => true, 'active' => false],
                                    ['label' => 'Tekst uitlezen', 'done' => in_array($processingStep, ['analyzing', 'done', 'failed']), 'active' => $processingStep === 'extracting'],
                                    ['label' => 'Suggesties', 'done' => in_array($processingStep, ['done', 'failed']), 'active' => $processingStep === 'analyzing'],
                                ];
                            @endphp
                            <div class="flex items-center gap-3 text-sm text-[var(--color-text-secondary)]">
                                @foreach($inlineSteps as $stepInfo)
                                    <span class="flex items-center gap-1.5 {{ $stepInfo['done'] ? 'text-[var(--color-primary)]' : ($stepInfo['active'] ? 'text-[var(--color-text-secondary)]' : 'text-[var(--color-text-secondary)]/50') }}">
                                        @if($stepInfo['done'])
                                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="M7.5 12.5L10.5 15.5L16.5 9.5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @elseif($stepInfo['active'])
                                            <svg class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.183" /></svg>
                                        @else
                                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/></svg>
                                        @endif
                                        {{ $stepInfo['label'] }}
                                    </span>
                                @endforeach
                                @if($processingStale)
                                    <button wire:click="skipProcessing" class="text-xs underline text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">Overslaan</button>
                                @endif
                            </div>
                        @endif
                    </div>
                    <flux:button variant="primary" wire:click="submitStep1" icon-trailing="arrow-right">
                        {{ empty($uploadedFiles) ? 'Verder zonder bestand' : 'Volgende' }}
                    </flux:button>
                </div>

                {{-- Step 2 footer --}}
                <div x-show="$wire.currentStep === 2" class="flex justify-between">
                    <flux:button variant="ghost" wire:click="goToStep(1)" icon="arrow-left">
                        Vorige
                    </flux:button>
                    <flux:button variant="primary" wire:click="submitStep2" icon-trailing="arrow-right">
                        Volgende
                    </flux:button>
                </div>

                {{-- Step 3 footer --}}
                <div x-show="$wire.currentStep === 3" class="flex justify-between">
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
    </section>
</div>
