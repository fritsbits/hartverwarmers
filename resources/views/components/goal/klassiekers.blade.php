@props(['items' => [], 'facetLetter' => 'D'])

@if(!empty($items))
    @php $colors = config('fiche-icons.colors'); @endphp

    <div class="mt-14">
        <h3 class="font-heading font-bold text-2xl mb-1.5">Zo maak je een diamantje van een klassieker</h3>
        <p class="text-[var(--color-text-secondary)] font-light">Je hoeft er geen nieuwe activiteit voor te bedenken.</p>

        <div class="mt-7 flex flex-col gap-3">
            @foreach($items as $index => $item)
                @php $color = $colors[$item['kleur']] ?? $colors[0]; @endphp
                <details class="klassieker" @if($index === 0) open @endif>
                    <summary>
                        <span class="klassieker-disc"
                              style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}">
                            @if(!empty($item['icoon']))
                                <x-dynamic-component :component="'lucide-' . $item['icoon']" class="w-8 h-8" />
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="klassieker-titel">{{ $item['titel'] }}</span>
                            <span class="klassieker-klassiek">{{ $item['klassiek'] }}</span>
                        </span>
                        <span class="klassieker-chev" aria-hidden="true">&#9662;</span>
                    </summary>

                    <div class="klassieker-body">
                        <p class="section-label">Zo kan het schitteren</p>

                        @foreach($item['verschuivingen'] ?? [] as $verschuiving)
                            <div class="verschuiving">
                                <p>{{ $verschuiving['voorbeeld'] }}</p>
                                <p class="verschuiving-principe">
                                    <x-diamant-gem :letter="$facetLetter" size="xxs" />
                                    <span><b>{{ $verschuiving['principe'] }}</b> &middot; {{ $verschuiving['toelichting'] }}</span>
                                </p>
                            </div>
                        @endforeach

                        <p class="klassieker-slot">Eén mogelijke versie. Niet de juiste.</p>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
@endif
