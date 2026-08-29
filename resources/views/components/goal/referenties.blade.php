@props(['items' => [], 'facetKeyword' => ''])

@if(!empty($items))
    <div>
        <span class="section-label">Verder kijken</span>
        <h2 class="mt-1 mb-2 font-heading font-bold text-[26px]">Wil je dieper graven rond <em>{{ $facetKeyword }}</em>?</h2>
        <p class="text-[var(--color-text-secondary)] font-light mb-6">Alles op dit lijstje wordt in de vormingen gebruikt of aanbevolen.</p>

        <div class="flex flex-col">
            @foreach($items as $ref)
                <a href="{{ $ref['url'] }}" class="referentie">
                    <span class="referentie-icoon" aria-hidden="true">
                        @switch($ref['type'])
                            @case('podcast') <flux:icon.microphone class="w-5 h-5" /> @break
                            @case('gids') <flux:icon.book-open class="w-5 h-5" /> @break
                            @case('materiaal') <flux:icon.archive-box class="w-5 h-5" /> @break
                            @default <flux:icon.puzzle-piece class="w-5 h-5" />
                        @endswitch
                    </span>
                    <span class="min-w-0">
                        <span class="referentie-type">{{ $ref['type'] }}</span>
                        <b class="block font-semibold text-[16.5px] leading-snug">{{ $ref['titel'] }}</b>
                        <small class="block text-[var(--color-text-secondary)] text-[14.5px] font-normal">{{ $ref['onder'] }}</small>
                    </span>
                    <span class="ml-auto text-[var(--color-text-tertiary)]" aria-hidden="true">&rarr;</span>
                </a>
            @endforeach
        </div>

        <p class="mt-4.5 text-sm text-[var(--color-text-tertiary)] font-normal">Het lijstje groeit het jaar door. Stuur gerust iets door.</p>
    </div>
@endif
