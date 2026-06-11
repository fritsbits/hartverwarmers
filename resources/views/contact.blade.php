<x-layout title="Contact" description="Een vraag, een idee of feedback? Laat van je horen — we lezen elk bericht zelf en bouwen Hartverwarmers verder op wat jij ons vertelt." :full-width="true" :hide-feedback-button="true">

    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                {{-- Left: warm invitation --}}
                <div>
                    <span class="section-label section-label-hero">Contact</span>
                    <h1 class="mt-1">Laat van je horen</h1>
                    <p class="text-xl text-[var(--color-text-secondary)] mt-4 max-w-xl" style="font-weight: var(--font-weight-light);">
                        Een vraag, een idee, of iets wat beter kan? We lezen elk bericht zelf. Vertel het ons — je helpt het platform mee vormgeven.
                    </p>

                    <div class="mt-8 space-y-5 max-w-md">
                        <div class="flex gap-3 items-start">
                            <span class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center shrink-0">
                                <flux:icon.pencil-square class="size-5 text-[var(--color-primary)]" />
                            </span>
                            <div>
                                <p class="font-semibold">Deel liever een activiteit?</p>
                                <a href="{{ route('fiches.create') }}" class="cta-link inline-block">Nieuwe fiche toevoegen</a>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <span class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center shrink-0">
                                <flux:icon.heart class="size-5 text-[var(--color-primary)]" />
                            </span>
                            <div>
                                <p class="font-semibold">Hartverwarmers steunen?</p>
                                <p class="text-[var(--color-text-secondary)] text-sm" style="font-weight: var(--font-weight-light);">Kies "Samenwerking of steun" in het formulier.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: form --}}
                <flux:card>
                    <livewire:support-contact-form :reason="$reason ?? ''" />
                </flux:card>

            </div>
        </div>
    </section>
</x-layout>
