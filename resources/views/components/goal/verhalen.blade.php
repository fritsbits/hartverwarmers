@props(['items' => []])

@if(!empty($items))
    <div x-data="{ actief: 0, aantal: {{ count($items) }} }">
        <span class="section-label">Verhalen uit de praktijk</span>
        <h2 class="mt-1 mb-2 font-heading font-bold text-[26px]">Het begon telkens klein</h2>
        <p class="text-[var(--color-text-secondary)] font-light mb-6">Echt gebeurd, in een huis zoals het jouwe.</p>

        <div class="relative">
            <div class="grid">
                @foreach($items as $index => $verhaal)
                    <div class="col-start-1 row-start-1 px-12 py-8 transition-opacity duration-300 ease-out motion-reduce:transition-none"
                         :class="actief === {{ $index }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                         :aria-hidden="actief === {{ $index }} ? 'false' : 'true'">
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
            <button type="button" class="verhaal-nav absolute left-0 top-1/2 -translate-y-1/2" @click="actief = (actief - 1 + aantal) % aantal" aria-label="Vorig verhaal">&lsaquo;</button>
            <button type="button" class="verhaal-nav absolute right-0 top-1/2 -translate-y-1/2" @click="actief = (actief + 1) % aantal" aria-label="Volgend verhaal">&rsaquo;</button>
        </div>

        <div class="flex justify-center gap-[7px] mt-1">
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
    </div>
@endif
