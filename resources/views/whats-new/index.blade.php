<x-layout title="Wat is er nieuw" description="Volg wat er maand na maand bijkomt en verbetert op Hartverwarmers." :full-width="true">

    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Nieuw</span>
                <h1 class="mt-1">Wat is er nieuw</h1>
                <p class="text-[var(--color-text-secondary)] mt-6 text-xl text-pretty" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers groeit stap voor stap, op basis van wat jullie ons vertellen. Hier zie je wat er onlangs is bijgekomen of verbeterd.
                </p>
            </div>
        </div>
    </section>

    <section>
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="max-w-4xl divide-y divide-[var(--color-border-light)]">
                @forelse ($updates as $update)
                    @php
                        $publishedAt = \Illuminate\Support\Carbon::parse($update['published_at']);
                        $isFresh = $publishedAt->gte(now()->subDays(\App\Services\ProductUpdates::FRESH_DAYS));
                    @endphp
                    <article class="py-12 first:pt-0 last:pb-0">
                        <a href="{{ route('whats-new.show', $update['uid']) }}"
                           class="group block md:grid md:grid-cols-[5.5rem_1fr] md:gap-x-10 lg:gap-x-14 rounded-[var(--radius-md)] focus-visible:outline-2 focus-visible:outline-offset-8 focus-visible:outline-[var(--color-primary)]">
                            <div class="mb-6 md:mb-0">
                                <x-date-stamp
                                    :date="$publishedAt"
                                    :badge="$isFresh ? ['label' => 'Nieuw', 'emphatic' => true] : null" />
                            </div>

                            <div class="sm:flex sm:items-start sm:gap-8">
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-3xl font-heading font-bold leading-tight text-balance transition-colors group-hover:text-[var(--color-primary)]">{{ $update['title'] }}</h2>
                                    <p class="text-[var(--color-text-secondary)] mt-3 max-w-[68ch] text-pretty line-clamp-3" style="font-weight: var(--font-weight-light);">{{ $update['body'] }}</p>
                                    <span class="cta-link mt-4">Lees meer</span>
                                </div>

                                @isset ($update['image'])
                                    <img src="{{ asset(ltrim($update['image']['src'], '/')) }}"
                                         alt=""
                                         loading="lazy"
                                         class="hidden sm:block shrink-0 w-44 aspect-[4/3] object-cover rounded-[var(--radius-md)] border border-[var(--color-border-light)] mt-1">
                                @endisset
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="text-[var(--color-text-secondary)]">Nog geen updates. Kom binnenkort nog eens kijken.</p>
                @endforelse
            </div>
        </div>
    </section>

</x-layout>
