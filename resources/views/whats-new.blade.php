<x-layout title="Wat is er nieuw" description="Volg wat er maand na maand bijkomt en verbetert op Hartverwarmers." :full-width="true">

    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Nieuw</span>
                <h1 class="mt-1">Wat is er nieuw</h1>
                <p class="text-[var(--color-text-secondary)] mt-6 text-xl" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers groeit stap voor stap, op basis van wat jullie ons vertellen. Hier zie je wat er onlangs is bijgekomen of verbeterd.
                </p>
            </div>
        </div>
    </section>

    <section>
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="max-w-3xl space-y-14">
                @forelse ($updates as $update)
                    <article>
                        <p class="section-label">{{ ucfirst(\Illuminate\Support\Carbon::parse($update['published_at'])->locale('nl_BE')->isoFormat('MMMM YYYY')) }}</p>
                        <h2 class="mt-1">{{ $update['title'] }}</h2>
                        <p class="text-[var(--color-text-secondary)] mt-3" style="font-weight: var(--font-weight-light);">{{ $update['body'] }}</p>
                        @isset ($update['link'])
                            <a href="{{ url($update['link']['url']) }}" class="cta-link inline-block mt-4">{{ $update['link']['label'] }}</a>
                        @endisset
                    </article>
                @empty
                    <p class="text-[var(--color-text-secondary)]">Nog geen updates. Kom binnenkort nog eens kijken.</p>
                @endforelse
            </div>
        </div>
    </section>

</x-layout>
