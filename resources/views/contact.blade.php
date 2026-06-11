<x-layout title="Contact" description="Een vraag, een idee of feedback? Je bericht komt rechtstreeks bij Frederik terecht — hij leest alles zelf en bouwt Hartverwarmers verder op wat jij vertelt." :full-width="true" :hide-feedback-button="true">

    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                {{-- Left: who you're writing to --}}
                <div>
                    <span class="section-label section-label-hero">Contact</span>
                    <h1 class="mt-1">Praat met Frederik</h1>
                    <p class="text-xl text-[var(--color-text-secondary)] mt-4 max-w-xl" style="font-weight: var(--font-weight-light);">
                        Een vraag, een idee, of iets wat beter kan? Je bericht komt rechtstreeks bij mij terecht — ik lees alles zelf en bouw Hartverwarmers verder op wat jij me vertelt.
                    </p>

                    {{-- Frederik: a face and a promise, so the form isn't addressed to no one --}}
                    <div class="mt-8 flex flex-col sm:flex-row gap-6 items-start max-w-xl">
                        <figure class="photo-polaroid shrink-0" style="transform: rotate(-2deg)">
                            <img src="/img/about/frederik-vincx.webp" alt="Frederik Vincx" width="160" height="120" class="w-40 aspect-[4/3] object-cover object-top">
                            <figcaption><strong class="text-[var(--color-text-primary)]">Frederik Vincx</strong></figcaption>
                        </figure>
                        <div>
                            <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">
                                Ik bouwde Hartverwarmers in 2020 en draag het sindsdien vrijwillig. Meestal antwoord ik binnen een paar dagen — soms iets later, want dit gebeurt in vrije uren.
                            </p>
                            <a href="https://www.frederikvincx.com/" target="_blank" rel="noopener noreferrer" class="cta-link inline-block mt-4 text-sm">Meer over mij</a>
                        </div>
                    </div>

                    <p class="mt-10 text-sm text-[var(--color-text-secondary)] max-w-xl" style="font-weight: var(--font-weight-light);">
                        Liever meteen een activiteit delen? <a href="{{ route('fiches.create') }}" class="cta-link inline-block">Nieuwe fiche toevoegen</a>
                    </p>
                </div>

                {{-- Right: form --}}
                <flux:card>
                    <livewire:support-contact-form :reason="$reason ?? ''" />
                </flux:card>

            </div>
        </div>
    </section>
</x-layout>
