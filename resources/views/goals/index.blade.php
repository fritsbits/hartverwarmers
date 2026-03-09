<x-layout title="Doelstellingen — DIAMANT-model" description="Het DIAMANT-model biedt 7 doelstellingen voor deugddoend samenleven in woonzorgcentra: Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie en Talent." :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Doelstellingen</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Het DIAMANT-model</span>
                <h1 class="mt-1">Zeven doelen om bewoners te laten schitteren</h1>
                <p class="text-2xl text-[var(--color-text-secondary)] mt-4" style="font-weight: var(--font-weight-light);">Het DIAMANT-model biedt activiteitenbegeleiders in de ouderenzorg een helder kader: zeven doelstellingen die samen beschrijven wat een deugddoende activiteit kenmerkt. Niet als afvinklijst, maar als kompas.</p>
                
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Split screen --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-12">
                {{-- Left column: facet cards --}}
                <div>
                    <span class="section-label">Doelstellingen</span>
                    <h2 class="mt-1 mb-2">Elke doelstelling vertrekt vanuit het perspectief van de bewoner</h2></p>
                    <p class="text-[var(--color-text-secondary)] my-3">Samen vormen ze de letters D-I-A-M-A-N-T — een geheugensteun die helpt om activiteiten bewuster te plannen, uit te voeren en te evalueren.</p>

                    <div class="space-y-4 mt-8">
                        @foreach($facets as $slug => $facet)
                            <a href="{{ route('goals.show', $slug) }}"
                               class="content-card group block" style="box-shadow: var(--shadow-card);">
                                <div class="card-content flex gap-5 items-start">
                                    <x-diamant-gem :letter="$facet['letter']" size="lg" />
                                    <div class="flex-1 min-w-0">
                                        <h3>{{ $facet['keyword'] }}</h3>
                                        <p class="mt-1 text-[var(--color-text-secondary)]">{{ $facet['subtitle'] }}</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Right column: sticky sidebar --}}
                <div class="mt-12 lg:mt-0 lg:sticky lg:top-8 lg:self-start">
                    {{-- Ontwikkeld door --}}
                    <div class="mb-8">
                        <span class="section-label">Ontwikkeld door</span>
                        <div class="flex gap-5 mt-4 justify-center">
                            <figure class="photo-polaroid" style="transform: rotate(-3deg)">
                                <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover">
                                <figcaption><strong class="text-[var(--color-text-primary)]">Maite Mallentjer</strong><br><small>Pedagoog dagbesteding</small></figcaption>
                            </figure>
                            <figure class="photo-polaroid -mt-2" style="transform: rotate(2.5deg)">
                                <img src="/img/wonen-en-leven/nadinepraet.jpg" alt="Nadine Praet" class="w-36 aspect-square object-cover">
                                <figcaption><strong class="text-[var(--color-text-primary)]">Nadine Praet</strong><br><small>Onderzoeker ouderenzorg</small></figcaption>
                            </figure>
                        </div>
                    </div>

                    <hr class="border-[var(--color-border-light)]">

                    {{-- Gebouwd op onderzoek --}}
                    <div class="mt-8">
                        <span class="section-label">Gebouwd op onderzoek</span>
                        <p class="text-[var(--color-text-secondary)] mt-3">Het DIAMANT-model bouwt voort op drie wetenschappelijke bronnen uit de Vlaamse ouderenzorg.</p>

                        <div class="space-y-8 mt-6">
                            {{-- BAM --}}
                            <div class="flex gap-5 items-start">
                                <a href="https://www.politeia.be/shop/16246-betekenisvolle-activiteiten-methode-11272#attr=" target="_blank" rel="noopener noreferrer" class="shrink-0">
                                    <img src="/img/covers/bam.jpg" alt="BAM boekcover" class="w-24 shadow-md" style="transform: rotate(-2deg);">
                                </a>
                                <div>
                                    <p class="text-lg font-semibold">Betekenisvolle Activiteiten Methode (BAM)</p>
                                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Cornelis, Vanbosseghem, Desmet & De Vriendt</p>
                                    <p class="text-[var(--color-text-secondary)] mt-1">Praktische methodiek voor het samen ontdekken van betekenisvolle activiteiten in woonzorgcentra.</p>
                                    <a href="https://www.politeia.be/shop/16246-betekenisvolle-activiteiten-methode-11272#attr=" target="_blank" rel="noopener noreferrer" class="cta-link mt-1 inline-block">Bekijk</a>
                                </div>
                            </div>

                            {{-- A Sense of Home --}}
                            <div class="flex gap-5 items-start">
                                <a href="https://www.politeia.be/shop/a-sense-of-home-15637#attr=71" target="_blank" rel="noopener noreferrer" class="shrink-0">
                                    <img src="/img/covers/a-sense-of-home.jpg" alt="A Sense of Home boekcover" class="w-24 shadow-md" style="transform: rotate(1.5deg);">
                                </a>
                                <div>
                                    <p class="text-lg font-semibold">A Sense of Home</p>
                                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Maite Mallentjer</p>
                                    <p class="text-[var(--color-text-secondary)] mt-1">Praktijkgericht onderzoek naar thuisgevoel en welbevinden in residentiële ouderenzorg.</p>
                                    <a href="https://www.politeia.be/shop/a-sense-of-home-15637#attr=71" target="_blank" rel="noopener noreferrer" class="cta-link mt-1 inline-block">Bekijk</a>
                                </div>
                            </div>

                            {{-- 't Klikt --}}
                            <div class="flex gap-5 items-start">
                                <a href="https://www.politeia.be/shop/t-klikt-15635#attr=65" target="_blank" rel="noopener noreferrer" class="shrink-0">
                                    <img src="/img/covers/t-klikt.jpg" alt="'t Klikt boekcover" class="w-24 shadow-md" style="transform: rotate(-1deg);">
                                </a>
                                <div>
                                    <p class="text-lg font-semibold">'t Klikt</p>
                                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Maite Mallentjer</p>
                                    <p class="text-[var(--color-text-secondary)] mt-1">Praktijkproject rond gemeenschapsvorming en het benutten van talenten in een woonzorgcentrum.</p>
                                    <a href="https://www.politeia.be/shop/t-klikt-15635#attr=65" target="_blank" rel="noopener noreferrer" class="cta-link mt-1 inline-block">Bekijk</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
