<div
    x-data="{
        ready: false,
        holding: false,
        interval: null,
        hearts: [],
        heartId: 0,
        didHold: false,
        pending: 0,
        serverTotal: {{ $this->totalKudos }},
        myGiven: {{ $this->myKudos }},
        hasGiven: {{ $this->myKudos > 0 ? 'true' : 'false' }},
        maxKudos: {{ $this->maxKudos }},
        isOwnFiche: {{ $this->isOwnFiche ? 'true' : 'false' }},
        isGuest: {{ auth()->check() ? 'false' : 'true' }},
        get displayTotal() {
            return this.serverTotal + this.pending;
        },
        get remaining() {
            return this.maxKudos - this.myGiven - this.pending;
        },
        get capped() {
            return this.remaining <= 0;
        },
        get disabled() {
            return this.isOwnFiche || this.capped || this.isGuest;
        },
        get tooltipText() {
            if (this.isOwnFiche) return 'Je kunt geen waardering geven aan je eigen fiche';
            if (this.isGuest) return 'Log in om waardering te geven';
            if (this.capped) return 'Je hebt het maximum van ' + this.maxKudos + ' waarderingen bereikt';
            return 'Klik of houd ingedrukt om waardering te geven';
        },
        init() {
            this.$nextTick(() => { this.ready = true; });
        },
        startHold() {
            if (this.disabled) return;
            this.holding = true;
            this.didHold = false;
            this.pending++;
            this.hasGiven = true;
            this.spawnHeart();
            this.interval = setInterval(() => {
                if (this.capped) {
                    this.stopHold();
                    return;
                }
                this.didHold = true;
                this.pending++;
                this.spawnHeart();
            }, 200);
        },
        stopHold() {
            this.holding = false;
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            if (this.pending > 0) {
                const amount = this.pending;
                this.serverTotal += amount;
                this.myGiven += amount;
                this.pending = 0;
                $wire.addKudos(amount);
            }
        },
        spawnHeart() {
            const id = this.heartId++;
            const x = (Math.random() - 0.5) * 50;
            const scale = 0.8 + Math.random() * 0.6;
            this.hearts.push({ id, x, scale });
            setTimeout(() => {
                this.hearts = this.hearts.filter(h => h.id !== id);
            }, 1000);
        }
    }"
    x-on:mouseup.window="stopHold()"
    x-on:touchend.window="stopHold()"
    class="flex flex-wrap items-center gap-4"
>
    {{-- Kudos button with floating hearts --}}
    <div class="relative">
        {{-- Floating hearts container --}}
        <div class="absolute inset-0 pointer-events-none overflow-visible">
            <template x-for="heart in hearts" :key="heart.id">
                <span
                    class="absolute select-none"
                    :style="`left: calc(50% + ${heart.x}px); top: 50%; font-size: ${heart.scale}rem; animation: kudosFloat 1s ease-out forwards; color: var(--color-primary);`"
                >&hearts;</span>
            </template>
        </div>

        <flux:tooltip position="top">
            <button
                x-on:mousedown.prevent="startHold()"
                x-on:touchstart.prevent="startHold()"
                class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-[var(--color-border-light)] select-none"
                :class="[
                    ready && 'transition-all',
                    disabled
                        ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] cursor-default opacity-60'
                        : holding
                            ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] scale-105 cursor-pointer'
                            : 'border-[var(--color-border-light)] hover:border-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)] cursor-pointer'
                ]"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="w-5 h-5 transition-transform"
                    :class="holding ? 'scale-125' : ''"
                    :fill="hasGiven ? 'var(--color-primary)' : 'none'"
                    stroke="var(--color-primary)"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                <span wire:ignore class="font-semibold text-sm tabular-nums" style="color: var(--color-primary)" x-text="displayTotal">
                    {{ $this->totalKudos }}
                </span>
            </button>
            <flux:tooltip.content>
                <span x-text="tooltipText"></span>
            </flux:tooltip.content>
        </flux:tooltip>
    </div>

    {{-- Bookmark toggle --}}
    <button
        wire:click="toggleBookmark"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border transition-all {{ $this->isBookmarked ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)]' : 'border-[var(--color-border-light)] hover:border-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)]' }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5"
            fill="{{ $this->isBookmarked ? 'var(--color-primary)' : 'none' }}"
            stroke="var(--color-primary)" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
        </svg>
        <span class="text-sm font-medium" style="color: var(--color-primary)">
            {{ $this->isBookmarked ? 'Favoriet' : 'Bewaar' }}
        </span>
    </button>

</div>
