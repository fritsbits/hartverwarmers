@php
    $isNewUser = auth()->check() && auth()->user()->created_at->gte(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')));
@endphp

@unless($isNewUser)
    <div
        x-data="{ show: !localStorage.getItem('whatsNewDismissed') }"
        x-show="show"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]"
    >
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center gap-4">
            <p class="flex-1 text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">
                Hartverwarmers is volledig vernieuwd. Ontdek wat er veranderd is.
                <a href="{{ route('whats-new') }}" class="cta-link ml-2">Lees meer</a>
            </p>
            <button
                @click="show = false; localStorage.setItem('whatsNewDismissed', 'true')"
                class="shrink-0 p-1 text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors cursor-pointer"
                aria-label="Sluiten"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endunless
