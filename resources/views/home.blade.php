<x-layout title="Ontdek activiteiten">
    <section class="text-center">
        <div class="py-16 max-w-200 mx-auto">
            <h1 class="mb-4">Deel deugddoende activiteiten</h1>
            <p class="text-base-content/70 text-xl font-light">Spaar tijd uit met honderden activiteiten en printbare bestanden ingezonden door activiteitenbegeleiders in de ouderenzorg.</p>
        </div>


    <!-- Featured Activities -->
    @if($activities->isNotEmpty())
        <section class="bg-white">
            <div class="max-w-6xl mx-auto px-6 py-12">   
                <h2 class="text-2xl mb-8 text-center">Recente activiteiten</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activities as $activity)
                        <x-activity-card :activity="$activity" />
                    @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('activities.index') }}" class="cta-link text-lg">
                    Bekijk alle activiteiten
                </a>
            </div>
            </div>
        </section>
    @endif

    <!-- Value Proposition -->
    <section class="bg-base-200 py-16">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <h2 class="mb-6">Waarom Hartverwarmers?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-10">
                <div>
                    <div class="text-primary text-4xl mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Uitgebreide bibliotheek</h3>
                    <p class="text-base-content/70">Honderden activiteiten voor elke doelgroep en interesse.</p>
                </div>
                <div>
                    <div class="text-primary text-4xl mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Print-klaar</h3>
                    <p class="text-base-content/70">Elke activiteit direct printbaar voor je team of leefgroep.</p>
                </div>
                <div>
                    <div class="text-primary text-4xl mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Door collega's</h3>
                    <p class="text-base-content/70">Activiteiten gedeeld door ervaren activiteitenbegeleiders.</p>
                </div>
            </div>
        </div>
    </section>
</x-layout>
