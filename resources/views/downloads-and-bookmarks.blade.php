<x-layout title="Downloads & favorieten">
    <div class="py-8">
        @if($isGuest)
            <div class="max-w-lg mx-auto text-center py-16">
                <h1>Bewaar je favoriete fiches</h1>
                <p>Sla inspirerende fiches op als favoriet en download ze om later te gebruiken. Zo heb je altijd ideeën bij de hand.</p>
                <a href="{{ route('register') }}">Maak een gratis account</a>
            </div>
        @else
            <h1>Downloads & favorieten</h1>
            @if($downloads->isEmpty())
                <p>Je hebt nog geen fiches gedownload.</p>
            @else
                @foreach($downloads as $fiche)
                    <div>{{ $fiche->title }}</div>
                @endforeach
            @endif
            @if($bookmarks->isEmpty())
                <p>Je hebt nog geen fiches als favoriet opgeslagen.</p>
            @else
                @foreach($bookmarks as $fiche)
                    <div>{{ $fiche->title }}</div>
                @endforeach
            @endif
        @endif
    </div>
</x-layout>
