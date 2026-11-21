@props(['content'])

<section class="bg-[var(--color-bg-base)]">
    <div class="max-w-6xl mx-auto px-6 py-16 text-center">
        <div class="bunting" aria-hidden="true">
            @foreach(str_split((string) $content->target) as $digit)
                <span class="bunting-flag">{{ $digit }}</span>
            @endforeach
        </div>

        @if($content->reached())
            <h2 class="text-3xl md:text-[40px] mt-4 mb-2">{{ $content->target }} activiteiten, gedeeld door jullie</h2>
            <p class="text-[var(--color-text-secondary)] text-lg md:text-[19px] max-w-[620px] mx-auto mb-6">Bedankt aan iedereen die een idee deelde.</p>
            <a href="{{ route('initiatives.index') }}" class="cta-link">Ontdek alle initiatieven</a>
        @else
            <h2 class="text-3xl md:text-[40px] mt-4 mb-2">Bijna <span class="text-[var(--color-primary)]">{{ $content->target }} activiteiten</span>!</h2>
            <p class="text-[var(--color-text-secondary)] text-lg md:text-[19px] max-w-[620px] mx-auto mb-8">{{ $content->fichesCount }} ideeën van collega's staan klaar. Nog {{ $content->remaining() }} en de teller staat op {{ $content->target }}. Deze thema's hebben nog weinig fiches.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                @foreach($content->needsLove->take(3) as $initiative)
                    <x-initiative-card :initiative="$initiative" :show-fiche-count="true" />
                @endforeach
            </div>

            <a href="{{ route('fiches.create') }}" class="btn-pill mt-8">Deel een activiteit &rarr;</a>
        @endif
    </div>
</section>
