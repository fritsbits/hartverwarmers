@php
    $isNewUser = auth()->check() && auth()->user()->created_at->gte(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')));
@endphp

@unless($isNewUser)
    <div
        x-data="{ show: !localStorage.getItem('whatsNewDismissed') }"
        x-show="show"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-cloak
        class="relative z-10 -mt-5"
    >
        <div class="max-w-6xl mx-auto px-6 pt-0 pb-0">
            <div class="relative bg-white rounded-[var(--radius-md)] border border-[var(--color-border-light)] shadow-card overflow-hidden">
                {{-- Warm accent stripe --}}
                <div class="absolute top-0 left-0 right-0 h-1 bg-[var(--color-primary)]"></div>

                <div class="px-6 py-5 sm:flex sm:items-center sm:gap-6">
                    {{-- Icon --}}
                    <div class="hidden sm:flex shrink-0 w-11 h-11 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                        </svg>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-heading font-bold text-lg text-[var(--color-text-primary)]">Hartverwarmers is volledig vernieuwd</p>
                        <p class="text-[var(--color-text-secondary)] mt-1" style="font-weight: var(--font-weight-light);">
                            Nieuwe structuur, betere navigatie en meer. Ontdek wat er veranderd is.
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 mt-4 sm:mt-0 shrink-0">
                        <a href="{{ route('whats-new') }}" class="btn-pill !py-2 !px-5 !text-sm">Ontdek wat er nieuw is</a>
                        <button
                            @click="show = false; localStorage.setItem('whatsNewDismissed', 'true')"
                            class="p-1.5 rounded-full text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)] transition-colors cursor-pointer"
                            aria-label="Sluiten"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endunless
