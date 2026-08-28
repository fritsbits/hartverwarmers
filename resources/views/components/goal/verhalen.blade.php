@props(['items' => []])

@if(!empty($items))
    <div x-data="{ actief: 0, aantal: {{ count($items) }} }">
        <span class="section-label">Verhalen uit de praktijk</span>
        <h2 class="mt-1 mb-2 font-heading font-bold text-[26px]">Het begon telkens klein</h2>
        <p class="text-[var(--color-text-secondary)] font-light mb-6">Echt gebeurd, in een huis zoals het jouwe.</p>

        <div class="overflow-hidden">
            <div class="flex transition-transform duration-300 ease-out"
                 :style="`transform: translateX(-${actief * 100}%)`">
                @foreach($items as $verhaal)
                    <div class="w-full shrink-0 px-8 py-8">
                        <figure class="quote-paper">
                            <span class="quote-paper-mark" aria-hidden="true">&rdquo;</span>
                            <p>{{ $verhaal['tekst'] }}</p>
                            <figcaption @class([
                                'block mt-3.5 font-body text-[13.5px] font-normal',
                                'text-[var(--color-text-secondary)]' => ($verhaal['status'] ?? 'klaar') === 'klaar',
                                'text-[var(--color-text-tertiary)] italic' => ($verhaal['status'] ?? 'klaar') !== 'klaar',
                            ])>{{ $verhaal['bron'] }}</figcaption>
                        </figure>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3.5 mt-2.5">
            <div class="flex gap-[7px]">
                @foreach($items as $index => $verhaal)
                    <button type="button"
                            class="p-2 -m-2 block"
                            :aria-current="actief === {{ $index }} ? 'true' : 'false'"
                            @click="actief = {{ $index }}"
                            aria-label="Verhaal {{ $index + 1 }}">
                        <span class="block w-2 h-2 rounded-full"
                              :class="actief === {{ $index }} ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-border-hover)]'"></span>
                    </button>
                @endforeach
            </div>
            <div class="ml-auto flex gap-2">
                <button type="button" class="verhaal-nav" @click="actief = (actief - 1 + aantal) % aantal" aria-label="Vorig verhaal">&lsaquo;</button>
                <button type="button" class="verhaal-nav" @click="actief = (actief + 1) % aantal" aria-label="Volgend verhaal">&rsaquo;</button>
            </div>
        </div>
    </div>
@endif
