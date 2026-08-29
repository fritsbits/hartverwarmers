@props([
    'items' => [],
    'principes' => [],
    'facetKeyword' => '',
])

@if(!empty($items))
    @php
        $colors = config('fiche-icons.colors');
        $legende = array_column($principes, 'naam');
    @endphp

    <div class="mt-16">
        <div class="max-w-[720px]">
            <span class="section-label">Klassiekers</span>
            <h2 class="mt-1 mb-3">Waar zit <em>{{ $facetKeyword }}</em> in een gewone wandeling?</h2>
            <p class="text-lg">Die vier principes komen terug in elke activiteit die je al draait.</p>
            <p class="text-lg font-light text-[var(--color-text-secondary)]">Drie kleine verschuivingen. Je hoeft er geen nieuwe activiteit voor te bedenken.</p>
        </div>

        <div class="mt-9 flex flex-col gap-3.5">
            @foreach($items as $item)
                @php
                    $color = $colors[$item['kleur']] ?? $colors[0];
                    $verschuivingen = $item['verschuivingen'] ?? [];

                    /**
                     * De chips staan in legendevolgorde, niet in die van de
                     * verschuivingen: zo valt de derde chip samen met de derde
                     * regel op het papier.
                     */
                    $chips = collect($verschuivingen)
                        ->pluck('principe')
                        ->filter()
                        ->unique()
                        ->sortBy(fn (string $naam) => array_search($naam, $legende, true) === false ? PHP_INT_MAX : array_search($naam, $legende, true))
                        ->values();
                @endphp

                <details class="klassieker">
                    <summary>
                        <span class="klassieker-disc"
                              style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}">
                            @if(!empty($item['icoon']))
                                <x-dynamic-component :component="'lucide-' . $item['icoon']" class="w-8 h-8" />
                            @endif
                        </span>

                        <span class="klassieker-titel">{{ $item['titel'] }}</span>

                        <span class="klassieker-lijf">
                            <span class="klassieker-statement">
                                <span class="klassieker-badge klassieker-badge-niet">NIET</span>
                                <span class="klassieker-klassiek">{{ $item['klassiek'] }}</span>
                            </span>

                            @if($chips->isNotEmpty())
                                <span class="klassieker-statement klassieker-belofte">
                                    <span class="klassieker-badge klassieker-badge-wel">WEL</span>
                                    <span class="klassieker-chips">
                                        @foreach($chips as $naam)
                                            <span class="klassieker-chip"><x-principe-gem :size="12" />{{ $naam }}</span>
                                        @endforeach
                                    </span>
                                </span>
                            @endif
                        </span>

                        @if(!empty($verschuivingen))
                            <span class="klassieker-toggle">
                                <span class="klassieker-label-dicht">Toon {{ count($verschuivingen) }} {{ count($verschuivingen) === 1 ? 'verschuiving' : 'verschuivingen' }}</span>
                                <span class="klassieker-label-open">Toon minder</span>
                                <svg class="klassieker-chev" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                     stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </span>
                        @endif
                    </summary>

                    @if(!empty($verschuivingen))
                        <div class="klassieker-body">
                            <div class="klassieker-statement">
                                <span class="klassieker-badge klassieker-badge-wel">WEL</span>
                                <div class="klassieker-verschuivingen">
                                    @foreach($verschuivingen as $verschuiving)
                                        <div class="verschuiving">
                                            <p>{{ $verschuiving['voorbeeld'] }}</p>
                                            <p class="verschuiving-principe">
                                                <x-principe-gem :size="13" />
                                                <span><b>{{ $verschuiving['principe'] }}</b> &middot; {{ $verschuiving['toelichting'] }}</span>
                                            </p>
                                        </div>
                                    @endforeach

                                    <p class="klassieker-slot">Eén mogelijke versie. Niet de juiste.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </details>
            @endforeach
        </div>
    </div>
@endif
