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
                <picture class="wizard-illustration hidden lg:block">
                    <source srcset="{{ asset('images/wizard/bestanden-uploaden.webp') }}" type="image/webp">
                    <img src="{{ asset('images/wizard/bestanden-uploaden.png') }}" alt="" aria-hidden="true" loading="lazy">
                </picture>
                <div class="relative mb-0">
                    <h2 class="relative z-10 text-3xl">Upload je bestanden</h2>
                </div>
                <p class="wizard-lead mb-8 relative z-10">Upload je bestanden — we analyseren de tekst en doen suggesties</p>

                <div class="space-y-10 relative z-10">
                    <flux:field>
                        <div
                            x-data="{
                                uploadError: '',
                                uploadTip: '',
                                uploading: false,
                                uploadProgress: 0,
                                displayProgress: 0,
                                devMode: @js($devMode),
                                fakeInterval: null,
                                realFinished: false,
                                hintIndex: -1,
                                hintTimeout: null,
                                hasConvertibleFile: false,
                                convertibleExtensions: ['pptx', 'ppt', 'docx', 'doc'],
                                get activeHints() {
                                    const base = [
                                        { icon: 'document-text', text: 'We lezen straks de tekst uit je bestand en doen slimme suggesties.' },
                                        { icon: 'sparkles', text: 'Wat maakt jouw activiteit uniek? Straks kun je dat beschrijven.' },
                                        { icon: 'users', text: 'Je fiche wordt zichtbaar voor alle collega\u2019s op het platform.' },
                                        { icon: 'document-plus', text: 'Heb je nog een bestand? Sleep het erbij \u2014 je kunt meerdere bestanden uploaden.' },
                                    ];
                                    if (this.hasConvertibleFile) {
                                        base.unshift({ icon: 'document-check', text: 'We maken automatisch een PDF-versie aan, zodat iedereen je bestand kan openen.' });
                                    }
                                    return base;
                                },
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
                                startUpload() {
                                    if (this.uploadError) return;
                                    this.uploading = true;
                                    this.realFinished = false;
                                    this.hintIndex = -1;

                                    if (this.devMode) {
                                        this.displayProgress = 0;
                                        this.startFakeProgress();
                                    }

                                    this.scheduleNextHint(2500);
                                },
                                scheduleNextHint(delay) {
                                    clearTimeout(this.hintTimeout);
                                    this.hintTimeout = setTimeout(() => {
                                        if (!this.uploading) return;
                                        const next = this.hintIndex + 1;
                                        if (next < this.activeHints.length) {
                                            this.hintIndex = next;
                                            this.scheduleNextHint(3500);
                                        }
                                    }, delay);
                                },
                                startFakeProgress() {
                                    clearInterval(this.fakeInterval);
                                    this.fakeInterval = setInterval(() => {
                                        if (this.displayProgress < 85) {
                                            this.displayProgress += Math.random() * 3 + 0.5;
                                        } else if (this.realFinished && this.displayProgress < 100) {
                                            this.displayProgress = Math.min(100, this.displayProgress + 5);
                                        }
                                        if (this.displayProgress >= 100) {
                                            this.finishUpload();
                                        }
                                    }, 200);
                                },
                                onProgress(progress) {
                                    if (this.uploadError) return;
                                    this.uploadProgress = progress;
                                    if (!this.devMode) {
                                        this.displayProgress = progress;
                                    }
                                },
                                onFinish() {
                                    if (this.devMode) {
                                        this.realFinished = true;
                                    } else {
                                        this.finishUpload();
                                    }
                                },
                                finishUpload() {
                                    this.uploading = false;
                                    this.uploadProgress = 0;
                                    this.realFinished = false;
                                    clearInterval(this.fakeInterval);
                                    clearTimeout(this.hintTimeout);
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
                                        if (this.convertibleExtensions.includes(ext)) {
                                            this.hasConvertibleFile = true;
                                        }
                                    }
                                    return true;
                                }
                            }"
                            x-on:livewire-upload-start.window="startUpload()"
                            x-on:livewire-upload-progress.window="onProgress($event.detail.progress)"
                            x-on:livewire-upload-finish.window="onFinish()"
                            x-on:livewire-upload-error.window="if (!uploadError) { uploadError = 'Het uploaden is mislukt. Controleer of het bestand kleiner is dan 50 MB en probeer het opnieuw.'; } finishUpload()"
                            x-on:livewire-upload-cancel.window="finishUpload()"
                            @upload-rejected.window="finishUpload()"
                            x-init="
                                const self = this;
                                $watch('uploadError', (value) => {
                                    if (value && self.uploading) {
                                        self.finishUpload();
                                    }
                                });
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
                            class="grid grid-cols-1 lg:grid-cols-2 gap-6"
                        >
                            {{-- Left: drop zone --}}
                            <div>
                                <flux:file-upload wire:model="uploads" multiple accept=".pdf,.pptx,.docx,.doc,.ppt,.jpg,.jpeg,.png">
                                    <flux:file-upload.dropzone
                                        heading="Sleep bestanden hierheen of klik om te bladeren"
                                        text="PDF, PPTX, DOCX, afbeeldingen — max 50MB per bestand"
                                    />
                                </flux:file-upload>
                            </div>

                            {{-- Right: upload progress + hints, error state, or file list --}}
                            <div class="space-y-3">
                                {{-- Upload in progress: educational hints --}}
                                <div x-show="uploading && !uploadError" x-cloak class="space-y-4">
                                    {{-- Progress bar --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="font-medium text-[var(--color-text-primary)]">Bezig met uploaden...</span>
                                            <span class="text-[var(--color-text-secondary)] tabular-nums" x-text="Math.round(displayProgress) + '%'"></span>
                                        </div>
                                        <div class="h-2 rounded-full bg-[var(--color-bg-subtle)] overflow-hidden">
                                            <div
                                                class="h-full rounded-full bg-[var(--color-primary)] transition-all duration-300 ease-out"
                                                :style="'width: ' + displayProgress + '%'"
                                            ></div>
                                        </div>
                                    </div>

                                    {{-- Rotating hints --}}
                                    <div x-show="hintIndex >= 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="rounded-xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] p-4 relative overflow-hidden min-h-[3.5rem]">
                                        <template x-for="(hint, i) in activeHints" :key="i">
                                            <div x-show="hintIndex === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200 absolute inset-x-4 top-4" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-[var(--color-primary)]/10 flex items-center justify-center shrink-0">
                                                    <template x-if="hint.icon === 'document-check'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm4.125 9.75l-3.75 3.75-1.875-1.875" /></svg>
                                                    </template>
                                                    <template x-if="hint.icon === 'document-text'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    </template>
                                                    <template x-if="hint.icon === 'sparkles'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" /></svg>
                                                    </template>
                                                    <template x-if="hint.icon === 'users'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                                    </template>
                                                    <template x-if="hint.icon === 'document-plus'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    </template>
                                                </div>
                                                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed" x-text="hint.text"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Client-side error (Alpine) --}}
                                <div wire:ignore x-show="uploadError" x-cloak>
                                    <flux:callout variant="warning" icon="exclamation-triangle">
                                        <flux:callout.heading><span x-text="uploadError"></span></flux:callout.heading>
                                        <flux:callout.text><p x-show="uploadTip" x-text="uploadTip"></p></flux:callout.text>
                                        <x-slot name="controls">
                                            <flux:button icon="x-mark" variant="ghost" x-on:click="uploadError = ''; uploadTip = ''" />
                                        </x-slot>
                                    </flux:callout>
                                </div>

                                {{-- Server-side error (Livewire) --}}
                                @error('uploads.*')
                                    <flux:callout variant="danger" icon="x-circle">
                                        <flux:callout.heading>{{ $message }}</flux:callout.heading>
                                    </flux:callout>
                                @enderror

                                {{-- File list --}}
                                @if(!empty($uploadedFiles))
                                    <div x-show="!uploadError && !uploading">
                                        <flux:label class="text-base font-body font-bold">Bestanden</flux:label>
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

                                    {{-- Copyright disclaimer --}}
                                    <label class="flex items-start gap-3 cursor-pointer mt-4">
                                        <flux:checkbox wire:model.live="disclaimerAccepted" />
                                        <span class="text-sm text-[var(--color-text-secondary)] leading-snug">
                                            Deze bestanden zijn mijn eigen werk en bevatten geen auteursrechtelijk beschermd materiaal van anderen (zoals foto's, illustraties of teksten waarvoor ik geen toestemming heb).
                                        </span>
                                    </label>
                                    @error('disclaimerAccepted')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
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
                <picture class="wizard-illustration hidden lg:block">
                    <source srcset="{{ asset('images/wizard/kernideeen-verzamelen.webp') }}" type="image/webp">
                    <img src="{{ asset('images/wizard/kernideeen-verzamelen.png') }}" alt="" aria-hidden="true" loading="lazy">
                </picture>
                <div class="relative mb-0">
                    <h2 class="relative z-10 text-3xl">Kerngegevens</h2>
                </div>
                <p class="wizard-lead mb-8 relative z-10">Vul de kerngegevens in terwijl we je bestanden analyseren</p>

                <div class="space-y-10 relative z-10">
                    {{-- Title --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                        <div>
                            <flux:field>
                                <flux:label class="text-base font-body font-bold">Titel <span class="field-tag ml-1">Verplicht</span></flux:label>
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
                            <flux:label class="text-base font-body font-bold">Duur</flux:label>
                            <flux:input wire:model="duration" placeholder="bijv. 30 min" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="text-base font-body font-bold">Groepsgrootte</flux:label>
                            <flux:input wire:model="groupSize" placeholder="bijv. 4-8" />
                        </flux:field>
                    </div>

                    {{-- DIAMANT goals — not user-selectable; assigned automatically via AI analysis --}}
                    {{-- Theme tags (hidden from UI — suggestions still applied automatically) --}}

                    {{-- Initiative linking --}}
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label class="text-base font-body font-bold">Gekoppeld initiatief <span class="field-tag ml-1">Verplicht</span></flux:label>
                            <flux:description>Koppel deze fiche aan een initiatief</flux:description>

                            {{-- Inline processing indicator --}}
                            @if(!$processingComplete && $processingStep !== 'idle' && $processingStep !== 'skipped')
                                @php
                                    $step2InlineSteps = [
                                        ['label' => 'Opladen', 'done' => true, 'active' => false],
                                        ['label' => 'Tekst uitlezen', 'done' => in_array($processingStep, ['analyzing', 'done', 'failed']), 'active' => $processingStep === 'extracting'],
                                        ['label' => 'Suggesties formuleren', 'done' => in_array($processingStep, ['done', 'failed']), 'active' => $processingStep === 'analyzing'],
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

                            {{-- No suggestions feedback --}}
                            @if($processingComplete && $processingFailReason && empty($matchedInitiatives))
                                <div class="mt-3 mb-4 flex items-start gap-2.5 rounded-xl bg-[var(--color-bg-subtle)] border border-[var(--color-border-light)] px-4 py-3 text-sm text-[var(--color-text-secondary)]">
                                    <flux:icon.information-circle class="w-5 h-5 shrink-0 mt-0.5" />
                                    @if($processingFailReason === 'no_text_extracted')
                                        <p>We konden geen tekst uitlezen uit je bestanden (bv. bij foto's of gescande PDF's). Kies hieronder zelf een initiatief.</p>
                                    @else
                                        <p>We konden geen initiatief voorstellen. Kies hieronder zelf een initiatief.</p>
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

                            {{-- Manual initiative select (always visible as fallback) --}}
                            @php
                                $hasAiSuggestions = !empty($matchedInitiatives);
                                $matchedIds = $hasAiSuggestions ? collect($matchedInitiatives)->pluck('id')->all() : [];
                                $initiativePlaceholder = $hasAiSuggestions ? 'Iets anders...' : 'Kies een initiatief...';
                            @endphp
                            <div class="mt-3 w-full sm:w-1/3">
                                <flux:select
                                    variant="listbox"
                                    wire:model.live="manualInitiativeId"
                                    wire:key="initiative-select-{{ $hasAiSuggestions ? 'ai' : 'manual' }}"
                                    :placeholder="$initiativePlaceholder"
                                >
                                    @foreach($allInitiatives as $init)
                                        @if(!in_array($init->id, $matchedIds))
                                            <flux:select.option :value="$init->id">{{ $init->title }}</flux:select.option>
                                        @endif
                                    @endforeach
                                </flux:select>
                                <flux:error name="selectedInitiativeId" />
                            </div>
                        </flux:field>
                    </div>
                </div>

            </div>

            {{-- ============================================== --}}
            {{-- Step 3: Inhoud                                 --}}
            {{-- ============================================== --}}
            <div x-show="$wire.currentStep === 3" x-cloak class="wizard-step-illustrated">
                <picture class="wizard-illustration hidden lg:block">
                    <source srcset="{{ asset('images/wizard/schrijven-en-componeren.webp') }}" type="image/webp">
                    <img src="{{ asset('images/wizard/schrijven-en-componeren.png') }}" alt="" aria-hidden="true" loading="lazy">
                </picture>
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
                            <flux:label class="text-base font-body font-bold">{{ $field['label'] }} @if($field['required'] ?? false)<span class="field-tag ml-1">Verplicht</span>@endif</flux:label>
                            <flux:description>{{ $field['description'] }}</flux:description>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                                {{-- User's editable field --}}
                                <div class="lg:col-span-7">
                                    <flux:editor
                                        wire:model="{{ $field['userProp'] }}"
                                        toolbar="bold | bullet ordered | link"
                                        placeholder="{{ $field['placeholder'] }}"
                                    />
                                </div>

                                {{-- Suggestion panel (always reserved) --}}
                                <div class="lg:col-span-5">
                                    @if($hasAiSuggestion)
                                        <x-ai-suggestion-panel
                                            :suggestion="$this->{$field['aiProp']}"
                                            :field="$field['field']"
                                            :is-applied="$isApplied"
                                        />
                                    @elseif($index === 0 && $processingComplete && $processingFailReason)
                                        <div class="flex gap-2.5 py-4 pl-2 pr-4 text-sm text-[var(--color-text-secondary)]">
                                            <flux:icon.information-circle class="w-5 h-5 shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="text-xs font-semibold text-[var(--color-text-secondary)] mb-2 uppercase tracking-wider">Geen suggesties</div>
                                                @if($processingFailReason === 'no_text_extracted')
                                                    <p>Je bestanden bevatten geen uitleesbare tekst (bv. foto's of gescande PDF's). Schrijf de inhoud zelf.</p>
                                                @else
                                                    <p>We konden geen suggesties genereren uit je bestanden. Schrijf de inhoud zelf.</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
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
                <picture class="wizard-illustration hidden lg:block">
                    <source srcset="{{ asset('images/wizard/resultaten-bekijken.webp') }}" type="image/webp">
                    <img src="{{ asset('images/wizard/resultaten-bekijken.png') }}" alt="" aria-hidden="true" loading="lazy">
                </picture>
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
                                    ['label' => 'Opladen', 'done' => true, 'active' => false],
                                    ['label' => 'Tekst uitlezen', 'done' => in_array($processingStep, ['analyzing', 'done', 'failed']), 'active' => $processingStep === 'extracting'],
                                    ['label' => 'Suggesties formuleren', 'done' => in_array($processingStep, ['done', 'failed']), 'active' => $processingStep === 'analyzing'],
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
                    <div class="flex items-center gap-4">
                        @if(empty($uploadedFiles))
                            <flux:button variant="primary" wire:click="submitStep1" icon-trailing="arrow-right">
                                Verder zonder bestand
                            </flux:button>
                        @elseif($disclaimerAccepted)
                            <flux:button variant="primary" wire:click="submitStep1" icon-trailing="arrow-right" class="animate-nudge" style="animation: nudge 0.5s cubic-bezier(0.25, 1, 0.5, 1) 0.15s both;">
                                Volgende
                            </flux:button>
                        @else
                            <flux:button variant="primary" icon-trailing="arrow-right" disabled>
                                Volgende
                            </flux:button>
                        @endif
                    </div>
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
                <div x-show="$wire.currentStep === 3">
                    @if($errors->has('description') || $errors->has('title') || $errors->has('selectedInitiativeId'))
                        <button
                            type="button"
                            class="w-full mb-3 px-4 py-2.5 rounded-xl bg-red-50 border border-red-200 flex items-center gap-2.5 text-sm text-red-800 hover:bg-red-100 transition-colors text-left"
                            x-on:click="
                                const firstError = document.querySelector('[data-flux-error]');
                                if (firstError) {
                                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>
                                @error('description') {{ $message }} @enderror
                                @error('title') {{ $message }} @enderror
                                @error('selectedInitiativeId') {{ $message }} @enderror
                                <span class="underline ml-1">Bekijk</span>
                            </span>
                        </button>
                    @endif
                    <div class="flex justify-between">
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
        </div>
    </section>
</div>
