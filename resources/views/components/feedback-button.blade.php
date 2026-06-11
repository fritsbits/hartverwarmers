<div x-data="{ open: false }" class="feedback-fab fixed bottom-5 right-5 z-40 print:hidden">
    {{-- Closed: pill (degrades to a plain link without JS). Anchored to the corner so it
         cross-morphs in place with the panel instead of shifting layout during the swap. --}}
    <a href="{{ route('contact', ['reden' => 'feedback']) }}"
       @click.prevent="open = true"
       x-show="!open"
       x-transition:enter="transition duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
       x-transition:enter-start="opacity-0 scale-90"
       x-transition:enter-end="opacity-100 scale-100"
       x-transition:leave="transition duration-150 ease-[cubic-bezier(0.4,0,1,1)]"
       x-transition:leave-start="opacity-100 scale-100"
       x-transition:leave-end="opacity-0 scale-90"
       :aria-expanded="open"
       aria-haspopup="dialog"
       class="absolute bottom-0 right-0 origin-bottom-right inline-flex items-center gap-2 whitespace-nowrap bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-semibold rounded-full px-5 py-3 shadow-lg transition-colors"
       aria-label="Geef feedback">
        <flux:icon.chat-bubble-left-ellipsis class="size-5" />
        Feedback
    </a>

    {{-- Open: panel grows out of the button's corner (origin-bottom-right). --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition duration-200 ease-[cubic-bezier(0.4,0,1,1)]"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         role="dialog"
         aria-label="Feedback geven"
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         x-init="$watch('open', value => value && $nextTick(() => $el.querySelector('button')?.focus()))"
         class="absolute bottom-0 right-0 origin-bottom-right w-72 max-w-[calc(100vw-2.5rem)] bg-white border border-[var(--color-border-light)] rounded-2xl shadow-2xl p-5">
        <button type="button" @click="open = false" aria-label="Sluiten"
                class="absolute top-3 right-3 text-[var(--color-text-tertiary)] hover:text-[var(--color-text-secondary)] transition-colors">
            <flux:icon.x-mark class="size-4" />
        </button>

        <h3 class="font-heading font-bold text-lg mb-1.5">Help je ons beter worden?</h3>
        <p class="text-sm text-[var(--color-text-secondary)] mb-4" style="font-weight: var(--font-weight-light);">
            Hartverwarmers is van en voor jou. Je mening telt echt.
        </p>

        <div class="space-y-2.5 mb-5">
            <div class="flex gap-2.5 items-start">
                <span class="w-6 h-6 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center shrink-0">
                    <flux:icon.heart class="size-3.5 text-[var(--color-primary)]" />
                </span>
                <span class="text-sm">Wat vind je nu al fijn?</span>
            </div>
            <div class="flex gap-2.5 items-start">
                <span class="w-6 h-6 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center shrink-0">
                    <flux:icon.pencil-square class="size-3.5 text-[var(--color-primary)]" />
                </span>
                <span class="text-sm">Wat zou je graag beter zien?</span>
            </div>
        </div>

        <a href="{{ route('contact', ['reden' => 'feedback']) }}" class="btn-pill w-full justify-center text-center block">
            Geef feedback &rarr;
        </a>
    </div>
</div>
