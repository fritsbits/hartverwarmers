{{-- Post-download takeover. Three Alpine sub-states: initial, kudos-given, commented. --}}
{{-- Lives INSIDE the FicheKudos Livewire component so wire:model + wire:click bind. --}}
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
     x-on:fiche-downloaded.window="if ($event.detail?.ficheId === fiche_id) { downloaded = true; init(); }"
     x-on:comment-added.window="goToConfirmation()"
     x-on:add-kudos.window="if ($event.detail.amount > 0) goToKudosGiven()"
     class="w-full mt-3"
>
<div x-show="downloaded" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
>
    {{-- State A: Initial — kudos + inline comment --}}
    <div x-show="state === 'initial'" x-cloak
         class="rounded-2xl p-5"
         style="background: linear-gradient(135deg, var(--color-bg-cream), var(--color-bg-accent-light)); border: 1px solid rgba(232, 118, 75, 0.2);">

        {{-- Author block --}}
        @if($contributor)
            <div class="flex flex-col items-center text-center mb-4">
                <x-user-avatar :user="$contributor" size="xl" class="mb-2 ring-2 ring-white shadow-md" />
                <span class="text-sm text-[var(--color-text-secondary)]">Door {{ $contributor->full_name }}</span>
            </div>
        @endif

        <h3 class="font-heading font-bold text-xl text-center mb-1" style="color: var(--color-text-primary)">
            {{ $contributorName }} deelde dit met jou
        </h3>
        <p class="text-sm text-center text-[var(--color-text-secondary)] mb-5">
            Maak {{ $contributorName }}'s dag — geef een hartje of laat een berichtje achter.
        </p>

        {{-- Kudos button (dispatches to FicheKudos via add-kudos event) --}}
        <div class="relative mb-1"
             x-data="{
                hearts: [], heartId: 0, given: 0, holding: false, interval: null,
                spawnHeart() {
                    const id = this.heartId++;
                    const dx = (Math.random() - 0.5) * 50;
                    const rot = (Math.random() - 0.5) * 30;
                    const scale = 0.7 + Math.random() * 0.4;
                    const dur = 1.2 + Math.random() * 0.6;
                    this.hearts.push({ id, dx, rot, scale, dur });
                    setTimeout(() => { this.hearts = this.hearts.filter(h => h.id !== id); }, dur * 1000 + 50);
                },
                startGive() {
                    if ({{ $isOwnFiche ? 'true' : 'false' }}) return;
                    this.holding = true;
                    this.given++;
                    this.spawnHeart();
                    this.interval = setInterval(() => { this.given++; this.spawnHeart(); }, 200);
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
            {{-- Floating hearts --}}
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
                    @if($isOwnFiche) disabled @endif
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[var(--color-primary)] text-white font-semibold transition-all hover:bg-[var(--color-primary-hover)] hover:shadow-md active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="holding ? 'scale-105' : ''">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform" :class="holding ? 'scale-125' : ''" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                Geef een hartje
            </button>
        </div>
        <p class="text-xs text-center text-[var(--color-text-secondary)] mb-5">hou ingedrukt voor meer</p>

        {{-- Inline comment box (auth only) --}}
        @auth
            @if(! $isOwnFiche)
                <div class="border-t border-[var(--color-border-light)] pt-4">
                    <label for="takeover-comment-{{ $fiche->id }}" class="block text-xs font-medium text-[var(--color-text-secondary)] mb-2">
                        …of schrijf een berichtje:
                    </label>
                    <textarea
                        id="takeover-comment-{{ $fiche->id }}"
                        x-ref="commentInput"
                        wire:model="body"
                        rows="3"
                        maxlength="1000"
                        placeholder="Schrijf hier..."
                        class="w-full px-3 py-2 rounded-lg border border-[var(--color-border-light)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 outline-none text-sm resize-y bg-white"
                    ></textarea>
                    @error('body')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end mt-2">
                        <button wire:click="addComment"
                                x-bind:disabled="$wire.body.trim().length < 2"
                                class="px-4 py-2 rounded-full bg-[var(--color-primary)] text-white text-sm font-semibold transition-all hover:bg-[var(--color-primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed">
                            Plaats
                        </button>
                    </div>
                </div>
            @endif
        @endauth

        <div class="text-center mt-4">
            <button x-on:click="dismiss()" class="text-xs text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                niet nu, bedankt
            </button>
        </div>
    </div>

    {{-- State B: Kudos given, inviting comment --}}
    <div x-show="state === 'kudosGiven'" x-cloak
         class="rounded-2xl p-5"
         style="background: linear-gradient(135deg, var(--color-bg-cream), var(--color-bg-accent-light)); border: 1px solid rgba(232, 118, 75, 0.2);">

        <div class="flex items-center justify-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="var(--color-primary)" viewBox="0 0 24 24">
                <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
            <span class="font-semibold text-sm" style="color: var(--color-primary)">Bedankt voor het hartje!</span>
        </div>

        @auth
            @if(! $isOwnFiche)
                <p class="text-sm text-center text-[var(--color-text-secondary)] mb-3">
                    Wil je {{ $contributorName }} ook nog een paar woorden meegeven? Het maakt écht verschil.
                </p>
                <textarea
                    x-ref="commentInputB"
                    wire:model="body"
                    rows="3"
                    maxlength="1000"
                    placeholder="Schrijf hier..."
                    class="w-full px-3 py-2 rounded-lg border border-[var(--color-border-light)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20 outline-none text-sm resize-y bg-white"
                ></textarea>
                @error('body')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
                <div class="flex justify-end mt-2">
                    <button wire:click="addComment"
                            x-bind:disabled="$wire.body.trim().length < 2"
                            class="px-4 py-2 rounded-full bg-[var(--color-primary)] text-white text-sm font-semibold transition-all hover:bg-[var(--color-primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed">
                        Plaats
                    </button>
                </div>
            @endif
        @endauth

        <div class="text-center mt-4">
            <button x-on:click="dismiss()" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors underline">
                niet nu, bedankt
            </button>
        </div>
    </div>

    {{-- State C: Commented — confirmation, auto-dismiss --}}
    <div x-show="state === 'commented'" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl p-5 text-center"
         style="background: linear-gradient(135deg, var(--color-bg-cream), var(--color-bg-accent-light)); border: 1px solid rgba(232, 118, 75, 0.2);"
         role="status">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--color-primary)]/10 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="var(--color-primary)" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        @auth
            <p class="font-semibold text-base" style="color: var(--color-text-primary)">
                Bedankt voor je berichtje, {{ auth()->user()->first_name }}.
            </p>
        @else
            <p class="font-semibold text-base" style="color: var(--color-text-primary)">Bedankt voor je berichtje.</p>
        @endauth
        <p class="text-sm text-[var(--color-text-secondary)] mt-1">
            {{ $contributorName }} krijgt een mailtje.
        </p>
    </div>

    {{-- Re-download link (uses $fiche->initiative since the takeover lives in FicheKudos scope) --}}
    <div class="text-center mt-3" x-show="state !== 'commented'" x-cloak>
        <a href="{{ route('fiches.download', [$fiche->initiative, $fiche]) }}"
           class="inline-flex items-center gap-1 text-xs text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Opnieuw downloaden
        </a>
    </div>
</div>
</div>

@push('scripts')
<script>
function postDownloadTakeover(ficheId) {
    return {
        fiche_id: ficheId,
        downloaded: false,
        state: 'initial',
        confirmationTimer: null,
        init() {
            // Pre-focus the comment box after a gentle delay (only when downloaded enters initial state).
            if (!this.downloaded) return;
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
            try { sessionStorage.setItem('takeover-dismissed-{{ $fiche->id }}', '1'); } catch (e) {}
        },
    };
}
</script>
@endpush
