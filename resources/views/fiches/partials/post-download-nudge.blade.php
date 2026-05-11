{{-- Post-download takeover: full-screen overlay with three Alpine sub-states. --}}
{{-- Lives inside the FicheKudos Livewire component so wire:model + wire:click bind. --}}
{{-- Triggered by the 'fiche-downloaded' window event dispatched by the download button. --}}
@auth
    @php
        $contributor = $fiche->user;
        $contributorName = $contributor?->first_name ?? 'de auteur';
        $isOwnFiche = auth()->id() === $fiche->user_id;
    @endphp
@else
    @php
        $contributor = $fiche->user;
        $contributorName = $contributor?->first_name ?? 'de auteur';
        $isOwnFiche = false;
    @endphp
@endauth

<div x-data="postDownloadTakeover({{ $fiche->id }})"
     x-on:fiche-downloaded.window="if ($event.detail?.ficheId === fiche_id) { open(); }"
     x-on:comment-added.window="goToConfirmation()"
     x-on:add-kudos.window="if ($event.detail.amount > 0) goToKudosGiven()"
     x-on:keydown.escape.window="if (downloaded) dismiss()"
>
<template x-teleport="body">
    <div x-show="downloaded" x-cloak
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
         style="background: rgba(35, 30, 26, 0.55); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); z-index: 9999;"
         x-on:click.self="dismiss()"
         role="dialog"
         aria-modal="true"
    >
        <div x-show="downloaded" x-cloak
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-10"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="relative w-full max-w-lg bg-[var(--color-bg-white)] rounded-3xl overflow-hidden"
             style="box-shadow:
                 0 0 0 1px rgba(232, 118, 75, 0.08),
                 0 1px 2px rgba(35, 30, 26, 0.04),
                 0 8px 24px -4px rgba(35, 30, 26, 0.12),
                 0 32px 64px -12px rgba(232, 118, 75, 0.32),
                 inset 0 1px 0 rgba(255, 255, 255, 0.6);"
             x-on:click.stop
        >
            {{-- Decorative warm gradient band at the top --}}
            <div class="absolute inset-x-0 top-0 h-32 pointer-events-none"
                 style="background: linear-gradient(180deg, var(--color-bg-accent-light) 0%, transparent 100%);"></div>

            {{-- Close X --}}
            <button x-on:click="dismiss()"
                    aria-label="Sluiten"
                    class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)] hover:text-[var(--color-text-primary)] transition-[background-color,color] duration-150 active:scale-[0.96] z-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- State A: Initial — kudos + inline comment --}}
            <div x-show="state === 'initial'" x-cloak class="relative px-6 sm:px-10 pt-12 pb-8">
                {{-- Heart motif — single accent, no aura, no sparkles, no shadow --}}
                <div class="flex justify-center mb-5" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14" fill="var(--color-primary)" viewBox="0 0 24 24" style="animation: takeoverHeartBreathe 3s ease-in-out infinite;">
                        <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                </div>

                <h2 class="font-heading font-bold text-3xl text-center leading-tight text-balance mb-3" style="color: var(--color-text-primary);">
                    {{ $contributorName }} deelde dit met jou
                </h2>

                <p class="text-base text-center text-pretty text-[var(--color-text-secondary)] mb-8 max-w-sm mx-auto">
                    Maak {{ $contributorName }}'s dag — geef een hartje of laat een berichtje achter.
                </p>

                {{-- Kudos button (press-and-hold) --}}
                <div class="relative mb-5"
                     x-data="{
                        hearts: [], heartId: 0, given: 0, holding: false, interval: null,
                        spawnHeart() {
                            const id = this.heartId++;
                            const dx = (Math.random() - 0.5) * 60;
                            const rot = (Math.random() - 0.5) * 35;
                            const scale = 0.9 + Math.random() * 0.5;
                            const dur = 1.2 + Math.random() * 0.6;
                            this.hearts.push({ id, dx, rot, scale, dur });
                            setTimeout(() => { this.hearts = this.hearts.filter(h => h.id !== id); }, dur * 1000 + 50);
                        },
                        startGive() {
                            if ({{ $isOwnFiche ? 'true' : 'false' }}) return;
                            this.holding = true;
                            this.given++;
                            this.spawnHeart();
                            this.interval = setInterval(() => { this.given++; this.spawnHeart(); }, 180);
                        },
                        stopGive() {
                            this.holding = false;
                            if (this.interval) { clearInterval(this.interval); this.interval = null; }
                            if (this.given > 0) {
                                $dispatch('add-kudos', { amount: this.given });
                                this.given = 0;
                            }
                        }
                     }"
                     x-on:mouseup.window="stopGive()"
                     x-on:touchend.window="stopGive()"
                >
                    <div class="absolute left-0 right-0 top-0 pointer-events-none overflow-visible" style="z-index: 50;">
                        <template x-for="heart in hearts" :key="heart.id">
                            <span class="absolute select-none"
                                  :style="`left: 50%; top: -4px; font-size: ${heart.scale}rem; --heart-dx: ${heart.dx}px; --heart-rot: ${heart.rot}deg; animation: kudosFloat ${heart.dur}s cubic-bezier(0.22, 1, 0.36, 1) forwards; color: var(--color-primary);`"
                            >&hearts;</span>
                        </template>
                    </div>
                    <button x-on:mousedown.prevent="startGive()"
                            x-on:touchstart.prevent="startGive()"
                            aria-label="Geef een hartje voor {{ $contributorName }}"
                            title="Hou ingedrukt voor meer"
                            @if($isOwnFiche) disabled @endif
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-[var(--color-primary)] text-white text-base font-semibold transition-[transform,background-color] duration-150 hover:bg-[var(--color-primary-hover)] active:scale-[0.96] disabled:opacity-50 disabled:cursor-not-allowed select-none"
                            :class="holding ? 'scale-[1.03]' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform" :class="holding ? 'scale-125' : ''" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                        Geef een hartje
                    </button>
                </div>

                {{-- Inline comment box (auth only) --}}
                @auth
                    @if(! $isOwnFiche)
                        <div class="relative">
                            <textarea
                                id="takeover-comment-{{ $fiche->id }}"
                                x-ref="commentInput"
                                wire:model.live="body"
                                rows="2"
                                maxlength="1000"
                                placeholder="…of schrijf een berichtje voor {{ $contributorName }}"
                                class="w-full px-4 py-3 rounded-2xl border border-[var(--color-border-light)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/15 outline-none text-base resize-none bg-[var(--color-bg-cream)] placeholder:text-[var(--color-text-secondary)]"
                            ></textarea>
                            @error('body')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div x-show="$wire.body && $wire.body.trim().length >= 2" x-cloak x-transition class="flex justify-end mt-2">
                                <button wire:click="addComment"
                                        class="px-5 py-2 rounded-full bg-[var(--color-primary)] text-white text-sm font-semibold transition-[transform,background-color] duration-150 hover:bg-[var(--color-primary-hover)] active:scale-[0.96]">
                                    Plaats
                                </button>
                            </div>
                        </div>
                    @endif
                @endauth

                <div class="text-center mt-6">
                    <button x-on:click="dismiss()" class="inline-flex items-center justify-center px-4 py-3 min-h-[40px] text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors duration-150">
                        niet nu, bedankt
                    </button>
                </div>
            </div>

            {{-- State B: Kudos given, inviting comment --}}
            <div x-show="state === 'kudosGiven'" x-cloak class="relative px-6 sm:px-10 pt-10 pb-8">
                <div class="flex flex-col items-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--color-primary)]/10 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="var(--color-primary)" viewBox="0 0 24 24">
                            <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-2xl sm:text-3xl text-center text-balance" style="color: var(--color-text-primary)">
                        Bedankt voor het hartje!
                    </h2>
                </div>

                @auth
                    @if(! $isOwnFiche)
                        <p class="text-base text-center text-pretty text-[var(--color-text-secondary)] mb-5 max-w-sm mx-auto">
                            Wil je {{ $contributorName }} ook nog een paar woorden meegeven? Het maakt écht verschil.
                        </p>
                        <textarea
                            x-ref="commentInputB"
                            wire:model="body"
                            rows="3"
                            maxlength="1000"
                            placeholder="Schrijf hier..."
                            class="w-full px-4 py-3 rounded-xl border border-[var(--color-border-light)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 outline-none text-base resize-y bg-white"
                        ></textarea>
                        @error('body')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end mt-3">
                            <button wire:click="addComment"
                                    x-bind:disabled="$wire.body.trim().length < 2"
                                    class="px-6 py-2.5 rounded-full bg-[var(--color-primary)] text-white text-sm font-bold transition-all hover:bg-[var(--color-primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed">
                                Plaats
                            </button>
                        </div>
                    @endif
                @endauth

                <div class="text-center mt-6">
                    <button x-on:click="dismiss()" class="inline-flex items-center justify-center px-4 py-3 min-h-[40px] text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors duration-150 underline underline-offset-2 decoration-[var(--color-border-light)] hover:decoration-[var(--color-primary)]">
                        niet nu, bedankt
                    </button>
                </div>
            </div>

            {{-- State C: Commented — confirmation, auto-dismiss --}}
            <div x-show="state === 'commented'" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="relative px-6 sm:px-10 py-12 text-center"
                 role="status">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-[var(--color-primary)]/10 mb-5" style="animation: takeoverContentEnter 0.4s ease-out 0ms both;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" stroke="var(--color-primary)" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" style="stroke-dasharray: 24; animation: checkmarkDraw 0.55s cubic-bezier(0.65, 0, 0.35, 1) 200ms both;" />
                    </svg>
                </div>
                @auth
                    <h2 class="font-heading font-bold text-2xl sm:text-3xl text-balance mb-2" style="color: var(--color-text-primary)">
                        Bedankt voor je berichtje, {{ auth()->user()->first_name }}.
                    </h2>
                @else
                    <h2 class="font-heading font-bold text-2xl sm:text-3xl text-balance mb-2" style="color: var(--color-text-primary)">
                        Bedankt voor je berichtje.
                    </h2>
                @endauth
                <p class="text-base text-[var(--color-text-secondary)]">
                    {{ $contributorName }} krijgt een mailtje.
                </p>
            </div>
        </div>
    </div>
</template>
</div>

<script>
if (typeof window.postDownloadTakeover === 'undefined') {
window.postDownloadTakeover = function(ficheId) {
    return {
        fiche_id: ficheId,
        downloaded: false,
        state: 'initial',
        confirmationTimer: null,
        open() {
            this.downloaded = true;
            this.state = 'initial';
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                setTimeout(() => {
                    if (this.state === 'initial' && this.$refs.commentInput) {
                        this.$refs.commentInput.focus({ preventScroll: true });
                    }
                }, 600);
            });
        },
        goToKudosGiven() {
            if (!this.downloaded) return;
            if (this.state === 'initial') {
                this.state = 'kudosGiven';
                this.$nextTick(() => {
                    if (this.$refs.commentInputB) {
                        this.$refs.commentInputB.focus({ preventScroll: true });
                    }
                });
            }
        },
        goToConfirmation() {
            if (!this.downloaded) return;
            this.state = 'commented';
            if (this.confirmationTimer) clearTimeout(this.confirmationTimer);
            this.confirmationTimer = setTimeout(() => { this.dismiss(); }, 5000);
        },
        dismiss() {
            if (this.confirmationTimer) clearTimeout(this.confirmationTimer);
            this.downloaded = false;
            this.state = 'initial';
            document.body.style.overflow = '';
            try { sessionStorage.setItem('takeover-dismissed-{{ $fiche->id }}', '1'); } catch (e) {}
        },
    };
};
}
</script>
