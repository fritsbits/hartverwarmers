<x-layout title="Diamantjes" description="Fiches die ons team uitkoos als bijzonder mooie voorbeelden van wat mogelijk is.">

    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex items-center gap-3 mb-4">
                <x-diamant-gem letter="" size="sm" :pronounced="true" />
                <h1 class="text-4xl">Diamantjes</h1>
            </div>
            <p class="text-[var(--color-text-secondary)] text-xl font-light max-w-2xl">
                Af en toe stoot ons team op een fiche die we gewoon te goed vinden om onopgemerkt te laten. Dit zijn ze.
            </p>
            <p class="mt-4 text-sm text-[var(--color-text-secondary)]">
                {{ $fiches->count() }} {{ $fiches->count() === 1 ? 'fiche' : 'fiches' }} uitgekozen door het team
            </p>
        </div>
    </section>

    {{-- Grid --}}
    <section class="bg-[var(--color-bg-base)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            @if($fiches->isEmpty())
                <p class="text-[var(--color-text-secondary)]">Er zijn nog geen diamantjes geselecteerd.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($fiches as $fiche)
                        <x-fiche-card :fiche="$fiche" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layout>
