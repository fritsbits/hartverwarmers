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
            return this.isOwnFiche || this.capped;
        },
        get tooltipText() {
            if (this.isOwnFiche) return 'Je kunt geen waardering geven aan je eigen fiche';
            if (this.capped) return 'Je hebt het maximum van ' + this.maxKudos + ' waarderingen bereikt';
            return 'Je kunt nog ' + this.remaining + ' waarderingen geven';
        },
        nudged: false,
        init() {
            this.$nextTick(() => { this.ready = true; });
        },
        handleNudge() {
            if (this.disabled) return;
            this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            this.nudged = true;
            setTimeout(() => {
                this.startHold();
                setTimeout(() => this.stopHold(), 250);
            }, 500);
            setTimeout(() => { this.nudged = false; }, 3000);
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
            const dx = (Math.random() - 0.5) * 50;
            const rot = (Math.random() - 0.5) * 30;
            const scale = 0.7 + Math.random() * 0.4;
            const dur = 1.2 + Math.random() * 0.6;
            this.hearts.push({ id, dx, rot, scale, dur });
            setTimeout(() => {
                this.hearts = this.hearts.filter(h => h.id !== id);
            }, dur * 1000 + 50);
        }
    }"
    x-on:mouseup.window="stopHold()"
    x-on:touchend.window="stopHold()"
    x-on:nudge-kudos.window="handleNudge()"
    x-on:add-kudos.window="if (!disabled && $event.detail.amount > 0) {
        const amt = Math.min($event.detail.amount, remaining);
        if (amt > 0) { serverTotal += amt; myGiven += amt; hasGiven = true; $wire.addKudos(amt); }
    }"
    class="flex flex-wrap items-center gap-4 transition-all duration-500 rounded-2xl -mx-3 px-3 -my-2 py-2"
    :class="nudged ? 'bg-[var(--color-bg-accent-light)] ring-2 ring-[var(--color-primary)]/30' : ''"
>
    {{-- Kudos button with floating hearts --}}
    <div class="relative">
        {{-- Floating hearts container — anchored to button top so hearts fan upward --}}
        <div class="absolute left-0 right-0 top-0 pointer-events-none overflow-visible" style="z-index: 50;">
            <template x-for="heart in hearts" :key="heart.id">
                <span
                    class="absolute select-none"
                    :style="`left: 50%; top: -4px; font-size: ${heart.scale}rem; --heart-dx: ${heart.dx}px; --heart-rot: ${heart.rot}deg; animation: kudosFloat ${heart.dur}s cubic-bezier(0.22, 1, 0.36, 1) forwards; color: var(--color-primary);`"
                >&hearts;</span>
            </template>
        </div>

        <flux:tooltip position="bottom">
            <button
                x-on:mousedown.prevent="startHold()"
                x-on:touchstart.prevent="startHold()"
                class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-full border select-none"
                :class="[
                    ready && 'transition-all',
                    disabled
                        ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] cursor-default opacity-60'
                        : holding
                            ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] scale-105 cursor-pointer'
                            : 'border-[var(--color-border-light)] bg-white hover:border-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)] cursor-pointer'
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
                <span class="text-sm font-medium" style="color: var(--color-primary)">
                    <span x-show="displayTotal === 0">Geef een hartje</span>
                    <span x-show="displayTotal > 0" x-cloak><span wire:ignore class="font-semibold tabular-nums" x-text="displayTotal">{{ $this->totalKudos > 0 ? $this->totalKudos : '' }}</span> hartjes gekregen</span>
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
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border transition-all font-medium {{ $this->isBookmarked ? 'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)]' : 'border-[var(--color-border-light)] bg-white hover:border-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)]' }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5"
            fill="{{ $this->isBookmarked ? 'var(--color-primary)' : 'none' }}"
            stroke="var(--color-primary)" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
        </svg>
        <span class="text-sm" style="color: var(--color-primary)">
            {{ $this->isBookmarked ? 'Opgeslagen als favoriet' : 'Bewaar als favoriet' }}
        </span>
    </button>

    {{-- Link to favorites overview (only after just bookmarking) --}}
    @if($justBookmarked)
        <a href="{{ route('bookmarks.index') }}" class="text-sm font-medium transition-colors hover:underline" style="color: var(--color-primary)" wire:transition>
            Je favorieten &rarr;
        </a>
    @endif

    {{-- Inline auth gate for bookmark (guests) --}}
    @if($showBookmarkAuth)
        <div class="w-full mt-2 bg-white rounded-xl border border-[var(--color-border-light)] p-4 shadow-sm"
             wire:transition>
            <p class="font-heading font-bold text-sm mb-0.5">Bewaar als favoriet</p>
            <p class="text-xs text-[var(--color-text-secondary)] mb-3">Vul je naam en e-mail in zodat we je favorieten kunnen onthouden.</p>
            <form wire:submit="guestBookmark">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                    <div>
                        <flux:input wire:model="guestName" placeholder="Je volledige naam" size="sm" />
                        @error('guestName') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:input wire:model="guestEmail" type="email" placeholder="je@email.be" size="sm" />
                        @error('guestEmail') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <label class="flex items-start gap-2 text-xs text-[var(--color-text-secondary)]">
                        <input type="checkbox" wire:model="guestTerms" class="mt-0.5 rounded border-[var(--color-border-light)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                        <span>Ik ga akkoord met de <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">gebruiksvoorwaarden</a></span>
                    </label>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="cancelBookmarkAuth" variant="ghost" size="xs">Annuleren</flux:button>
                        <flux:button type="submit" variant="primary" size="xs">Bewaar</flux:button>
                    </div>
                </div>
                @error('guestTerms') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </form>
        </div>
    @endif

    @include('fiches.partials.post-download-nudge')

</div>
