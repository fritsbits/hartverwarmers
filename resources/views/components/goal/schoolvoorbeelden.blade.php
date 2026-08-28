@props(['items' => [], 'facetKeyword' => ''])

@if(count($items) > 0)
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Uitgelicht</span>
            <h2 class="mt-1 mb-2">Zo ziet {{ mb_strtolower($facetKeyword) }} eruit</h2>
            <p class="text-[var(--color-text-secondary)] font-light mb-8 max-w-2xl">
                Fiches waarin bewoners echt zelf aan zet zijn, met telkens één zin waarom.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($items as $item)
                    @php $initiative = $item['fiche']->initiative; @endphp
                    <a href="{{ route('fiches.show', [$initiative, $item['fiche']]) }}" class="block cursor-pointer">
                        <flux:card class="flex flex-col gap-2.5 h-full overflow-hidden !p-0 border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-[transform,box-shadow,border-color] duration-200">
                            @if($initiative->image)
                                <img src="{{ $initiative->thumbnailUrl() ?? $initiative->image }}" alt="{{ $initiative->title }}" class="w-full aspect-[16/10] object-cover" loading="lazy" decoding="async">
                            @else
                                <div class="bg-[var(--color-bg-cream)] aspect-[16/10] flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            @endif

                            <div class="flex flex-col gap-2.5 px-4 pb-4 flex-1">
                                <flux:heading class="font-heading font-bold">{{ $item['fiche']->title }}</flux:heading>
                                <p class="text-sm text-[var(--color-text-secondary)] font-normal">
                                    @if($item['fiche']->user)
                                        {{ $item['fiche']->user->first_name }} {{ $item['fiche']->user->last_name }} &middot;
                                    @endif
                                    {{ $initiative->title }}
                                </p>
                                <p class="mt-auto bg-[var(--color-bg-accent-light)] rounded-lg px-3.5 py-2.5 text-[15px] font-normal leading-relaxed">
                                    <b class="font-semibold text-[var(--color-primary)]">Waarom:</b> {{ $item['waarom'] }}
                                </p>
                            </div>
                        </flux:card>
                    </a>
                @endforeach
            </div>

            <p class="mt-7 text-sm text-[var(--color-text-tertiary)] font-normal">
                Meer fiches over {{ mb_strtolower($facetKeyword) }} volgen zodra de fichepagina met filters er is.
            </p>
        </div>
    </section>
@endif
