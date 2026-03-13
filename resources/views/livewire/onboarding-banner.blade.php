<div>
@if($level === 1)
    <section class="bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex items-start gap-5 mb-8">
                <div class="flex-1">
                    <span class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                        Welkom, {{ auth()->user()->first_name }}!
                    </span>
                    <h2 class="text-3xl mb-2">Dit kan je nu allemaal</h2>
                    <p class="text-[var(--color-text-secondary)] max-w-2xl">
                        Fijn dat je erbij bent. Hier zijn de dingen die je als lid kan doen op Hartverwarmers.
                    </p>
                </div>
                <button wire:click="dismiss" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors cursor-pointer shrink-0 mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Bewaar favorieten --}}
                <div class="content-card p-5 flex flex-col gap-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold">Bewaar favorieten</h3>
                    <p class="text-sm text-[var(--color-text-secondary)]">Klik op het bladwijzer-icoon bij een fiche om ze te bewaren voor later.</p>
                </div>

                {{-- Bedank collega's --}}
                <div class="content-card p-5 flex flex-col gap-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold">Geef kudos</h3>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Klik op het hartje bij een fiche om de auteur te bedanken.
                        @if($this->sessionKudosCount > 0)
                            <span class="block mt-1 font-semibold text-[var(--color-primary)]">Je hebt al {{ $this->sessionKudosCount }} kudos gegeven!</span>
                        @endif
                    </p>
                </div>

                {{-- Deel je mening --}}
                <div class="content-card p-5 flex flex-col gap-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold">Reageer op fiches</h3>
                    <p class="text-sm text-[var(--color-text-secondary)]">Stel een vraag, deel een tip of vertel hoe jij het aanpakte.</p>
                </div>

                {{-- Deel je eigen fiche (CTA) --}}
                <a href="{{ route('fiches.create') }}" class="content-card p-5 flex flex-col gap-3 no-underline border-[var(--color-primary)] border-2 bg-[var(--color-bg-accent-light)]">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold text-[var(--color-text-primary)]">Schrijf een fiche</h3>
                    <p class="text-sm text-[var(--color-text-secondary)]">Heb je zelf een activiteit uitgewerkt? Deel ze met collega's.</p>
                </a>
            </div>

        </div>
    </section>
@elseif($level === 2)
    <section class="border-t border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto px-6 py-5">
            <a href="{{ route('profile.show') }}" class="flex items-center gap-4 p-4 rounded-xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] hover:border-[var(--color-primary)] transition-all no-underline group hover:shadow-card-hover">
                <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-[var(--color-text-primary)]">
                        Je hebt je eerste fiche gedeeld — geweldig!
                    </p>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-0.5">
                        Laat andere hartverwarmers weten wie jij bent.
                    </p>
                </div>
                <span class="btn-pill text-sm shrink-0 group-hover:shadow-md transition-shadow">Stel je voor</span>
            </a>

            @feature('diamant-goals')
            @if(count($this->underservedGoals) > 0)
                <div class="mt-3 flex items-center gap-4 p-4 rounded-xl bg-white border border-[var(--color-border-light)]">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[var(--color-text-primary)]">We zoeken nog fiches rond deze doelen</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($this->underservedGoals as $goal)
                                <a href="{{ route('goals.show', $goal['slug']) }}" class="diamant-pill">
                                    <x-diamant-gem size="xxs" />
                                    {{ $goal['keyword'] }}
                                    <span class="text-[var(--color-text-secondary)] font-normal">{{ $goal['fiche_count'] }} {{ $goal['fiche_count'] === 1 ? 'fiche' : 'fiches' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            @endfeature
        </div>
    </section>
@endif
</div>
