@if($showReturnVisitBanner ?? false)
    <div
        x-data="{
            dismissed: sessionStorage.getItem('return-banner-dismissed-{{ $fiche->id }}') === '1',
            dismiss() { this.dismissed = true; try { sessionStorage.setItem('return-banner-dismissed-{{ $fiche->id }}', '1'); } catch(e) {} }
        }"
        x-show="!dismissed"
        x-cloak
        class="mb-4 origin-top"
    >
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-[var(--color-primary)]/20 bg-[var(--color-bg-cream)] px-4 py-3">
            <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-[var(--color-primary)]/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="var(--color-primary)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm">
                    <span class="font-semibold">Je downloadde '{{ $fiche->title }}'</span>@if($lastDownloadDate ?? null) op {{ $lastDownloadDate->translatedFormat('j M') }}@endif.
                </p>
                <p class="text-xs text-[var(--color-text-secondary)]">
                    Bedank {{ $fiche->user?->first_name ?? 'de auteur' }} — een hartje is genoeg.
                </p>
            </div>
            <a href="#kudos-and-bookmark"
               x-on:click="$dispatch('nudge-kudos')"
               class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[var(--color-primary)] text-white text-sm font-semibold hover:bg-[var(--color-primary-hover)] transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
                Bedank
            </a>
            <button x-on:click="dismiss()"
                    aria-label="Banner sluiten"
                    class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full hover:bg-[var(--color-bg-subtle)] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
