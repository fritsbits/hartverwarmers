@php
    if (! (auth()->user()?->isAdmin() || auth()->user()?->isCurator())) {
        return;
    }

    $suggestions = \App\Models\Fiche::query()
        ->published()
        ->where('has_diamond', false)
        ->whereNotNull('quality_score')
        ->where('quality_score', '>=', 70)
        ->with(['initiative'])
        ->orderByDesc('quality_score')
        ->orderByDesc('created_at')
        ->limit(6)
        ->get()
        ->map(fn ($fiche) => [
            'id'         => $fiche->id,
            'title'      => $fiche->title,
            'score'      => $fiche->quality_score,
            'url'        => route('fiches.show', [$fiche->initiative, $fiche]),
            'diamondUrl' => route('fiches.toggleDiamond', [$fiche->initiative, $fiche]),
        ]);

    if ($suggestions->isEmpty()) {
        return;
    }
@endphp

<div
    x-data='{
        allFiches: @json($suggestions),
        dismissed: [],
        openMenu: null,
        visibleFiches() {
            return this.allFiches
                .filter(f => !this.dismissed.includes(f.id))
                .slice(0, 3)
        },
        init() {
            this.dismissed = JSON.parse(localStorage.getItem("dismissed_diamond_suggestions") || "[]")
        },
        dismiss(id) {
            this.dismissed.push(id)
            localStorage.setItem("dismissed_diamond_suggestions", JSON.stringify(this.dismissed))
            this.openMenu = null
        }
    }'
    x-init="init()"
    x-show="visibleFiches().length > 0"
    class="rounded-xl border border-[var(--color-border-light)]"
>
    {{-- Header --}}
    <div class="bg-[var(--color-bg-subtle)] px-5 py-4 border-b border-[var(--color-border-light)] rounded-t-xl">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-[var(--color-bg-cream)] flex items-center justify-center shrink-0">
                <x-diamant-gem size="xxs" :pronounced="true" />
            </div>
            <div>
                <h3 class="font-heading font-bold text-sm">Kandidaten voor een diamantje</h3>
                <p class="text-xs text-[var(--color-text-tertiary)]">Recente fiches met hoge score</p>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="bg-white divide-y divide-[var(--color-border-light)] rounded-b-xl overflow-hidden">
        <template x-for="fiche in visibleFiches()" :key="fiche.id">
            <div class="flex items-center gap-2 px-4 py-2.5 relative">
                {{-- Title link --}}
                <a
                    :href="fiche.url"
                    class="flex-1 text-sm text-[var(--color-text-primary)] truncate no-underline hover:text-[var(--color-primary)] transition-colors"
                    x-text="fiche.title"
                ></a>

                {{-- Score badge --}}
                <span class="text-xs bg-[var(--color-bg-subtle)] border border-[var(--color-border-light)] text-[var(--color-text-secondary)] px-1.5 py-0.5 rounded shrink-0" x-text="fiche.score"></span>

                {{-- Three-dot menu --}}
                <div class="relative shrink-0" @click.outside="if (openMenu === fiche.id) openMenu = null">
                    <button
                        @click="openMenu = openMenu === fiche.id ? null : fiche.id"
                        class="w-6 h-6 flex items-center justify-center rounded border border-[var(--color-border-light)] bg-[var(--color-bg-subtle)] text-[var(--color-text-tertiary)] hover:border-[var(--color-border-hover)] hover:text-[var(--color-text-secondary)] transition-colors text-sm font-bold"
                        title="Opties"
                    >···</button>

                    {{-- Dropdown --}}
                    <div
                        x-show="openMenu === fiche.id"
                        x-transition
                        class="absolute right-0 top-full mt-1 z-20 bg-white border border-[var(--color-border-light)] rounded-lg shadow-lg min-w-[220px] overflow-hidden"
                    >
                        {{-- Assign diamond --}}
                        <form :action="fiche.diamondUrl" method="POST">
                            @csrf
                            <input type="hidden" name="_redirect" value="{{ route('diamantjes.index') }}">
                            <button
                                type="submit"
                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle)] transition-colors border-b border-[var(--color-border-light)] whitespace-nowrap"
                            >
                                <x-diamant-gem size="xxs" :pronounced="true" />
                                Toekennen als diamantje
                            </button>
                        </form>

                        {{-- Dismiss --}}
                        <button
                            @click="dismiss(fiche.id)"
                            class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)] transition-colors whitespace-nowrap"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            Niet meer tonen
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
