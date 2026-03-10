{{-- Post-download: animated give-back nudge --}}
<div x-show="downloaded" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="text-center py-2"
     x-data="{
        hearts: [],
        heartId: 0,
        given: 0,
        holding: false,
        interval: null,
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
            this.holding = true;
            this.given++;
            this.spawnHeart();
            this.interval = setInterval(() => {
                this.given++;
                this.spawnHeart();
            }, 200);
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
    {{-- Animated checkmark + mini confetti --}}
    <div class="relative inline-flex items-center justify-center mb-3">
        {{-- Mini confetti burst --}}
        <template x-if="downloaded">
            @for($i = 0; $i < 8; $i++)
                <span class="absolute w-1.5 h-1.5 rounded-full opacity-0"
                      style="background: {{ ['var(--color-primary)', 'var(--color-secondary)', 'var(--color-yellow)', 'var(--color-accent-purple)'][$i % 4] }};
                             animation: confettiFall 0.8s ease-out {{ $i * 0.05 }}s forwards;
                             transform-origin: center;
                             left: calc(50% + {{ (($i % 2 === 0 ? 1 : -1) * (8 + $i * 4)) }}px);
                             top: calc(50% - {{ 5 + $i * 3 }}px);">
                </span>
            @endfor
        </template>

        {{-- Animated checkmark SVG --}}
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" opacity="0.2" />
                <path d="M9 12.75L11.25 15 15 9.75"
                      style="stroke-dasharray: 24; animation: checkmarkDraw 0.5s ease-out 0.2s both;" />
            </svg>
            <span class="font-semibold text-sm" style="color: var(--color-primary)">Download gestart!</span>
        </div>
    </div>

    <p class="text-sm text-[var(--color-text-secondary)] mb-4">Vond je deze fiche waardevol?</p>

    <div class="flex gap-2">
        <a href="#reacties"
           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-[var(--color-primary)]/20 font-semibold text-sm transition-all hover:bg-[var(--color-bg-accent-light)] hover:border-[var(--color-primary)]/40"
           style="color: var(--color-primary)">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            Bedank
        </a>
        {{-- Inline hearts "Geef een hartje" button --}}
        <div class="relative flex-1">
            <div class="absolute left-0 right-0 top-0 pointer-events-none overflow-visible" style="z-index: 50;">
                <template x-for="heart in hearts" :key="heart.id">
                    <span class="absolute select-none"
                          :style="`left: 50%; top: -4px; font-size: ${heart.scale}rem; --heart-dx: ${heart.dx}px; --heart-rot: ${heart.rot}deg; animation: kudosFloat ${heart.dur}s cubic-bezier(0.22, 1, 0.36, 1) forwards; color: var(--color-primary);`"
                    >&hearts;</span>
                </template>
            </div>
            <button x-on:mousedown.prevent="startGive()"
                    x-on:touchstart.prevent="startGive()"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--color-primary)] text-white font-semibold text-sm transition-all hover:bg-[var(--color-primary-hover)] hover:shadow-md active:scale-[0.98]"
                    :class="holding ? 'scale-105' : ''">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 transition-transform" :class="holding ? 'scale-125' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                Geef een hartje
            </button>
        </div>
    </div>

    {{-- Re-download link --}}
    <a href="{{ route('fiches.download', [$initiative, $fiche]) }}"
       class="inline-flex items-center gap-1 mt-3 text-xs text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Opnieuw downloaden
    </a>
</div>
