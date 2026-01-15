<x-layout title="Themakalender">
    <div class="container mx-auto px-6 py-12">
        <div class="intro-block py-8">
            <h1>Themakalender</h1>
            <p>Ontdek thema's, feestdagen en speciale momenten.</p>
        </div>

        <div class="flex justify-between items-center mb-8">
            <a href="{{ $previousUrl }}" class="btn btn-ghost gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Vorige
            </a>
            <a href="{{ $nextUrl }}" class="btn btn-ghost gap-2">
                Volgende
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($themesByMonth->isEmpty())
            <div class="text-center py-12">
                <p class="text-base-content/60">Geen thema's in deze periode.</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($themesByMonth as $monthKey => $themes)
                    @php
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->locale('nl');
                    @endphp
                    <section>
                        <h2 class="text-xl font-bold mb-4 text-primary">
                            {{ ucfirst($monthDate->translatedFormat('F Y')) }}
                        </h2>
                        <div class="card bg-base-100 shadow-sm">
                            <div class="card-body p-0">
                                <ul class="divide-y divide-base-200">
                                    @foreach($themes as $theme)
                                        <li class="flex items-center gap-4 px-4 py-3">
                                            <span class="text-sm font-medium text-base-content/60 w-16 shrink-0">
                                                {{ $theme->start->locale('nl')->translatedFormat('d M') }}
                                            </span>
                                            <span class="font-medium">{{ $theme->title }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

        <div class="flex justify-between items-center mt-8">
            <a href="{{ $previousUrl }}" class="btn btn-ghost gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Vorige
            </a>
            <a href="{{ $nextUrl }}" class="btn btn-ghost gap-2">
                Volgende
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</x-layout>
