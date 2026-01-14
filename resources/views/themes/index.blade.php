<x-layout title="Kalender">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="intro-block py-8">
            <h1>Themakalender</h1>
            <p>Vind activiteiten rondom seizoenen, feestdagen en speciale momenten.</p>
        </div>

        @if($themes->isEmpty())
            <div class="text-center py-12">
                <p class="text-base-content/60">Nog geen thema's beschikbaar.</p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach($themes as $theme)
                    <a href="{{ route('themes.show', $theme) }}" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="card-body">
                            <h2 class="card-title">{{ $theme->title }}</h2>
                            @if($theme->start && $theme->end)
                                <p class="text-sm text-base-content/60">
                                    {{ $theme->start->format('d M') }} - {{ $theme->end->format('d M Y') }}
                                </p>
                            @endif
                            @if($theme->description)
                                <p class="text-base-content/70">{{ Str::limit($theme->description, 150) }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
