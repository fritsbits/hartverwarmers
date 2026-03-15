<x-layout title="Mijn fiches">
    <div class="py-8">
        @if($isGuest)
            <div class="max-w-lg mx-auto text-center py-16">
                <h1>Deel jouw ervaring met collega's</h1>
                <p>Schrijf een fiche en help andere animatoren met praktische ideeën.</p>
                <a href="{{ route('register') }}">Maak een gratis account</a>
            </div>
        @else
            <h1>Mijn fiches</h1>
            @if($newCommentsCount > 0)
                <p>Je hebt <strong>{{ $newCommentsCount }}</strong> nieuwe {{ $newCommentsCount === 1 ? 'reactie' : 'reacties' }} op je fiches.</p>
            @endif
            @if($fiches->isNotEmpty())
                @foreach($fiches as $fiche)
                    <div>{{ $fiche->title }}</div>
                @endforeach
            @else
                <p>Je hebt nog geen fiches geschreven.</p>
            @endif
        @endif
    </div>
</x-layout>
