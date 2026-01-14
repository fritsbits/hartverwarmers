<x-layout :title="$theme->title">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <nav class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('themes.index') }}">Kalender</a></li>
                <li>{{ $theme->title }}</li>
            </ul>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl mb-2">{{ $theme->title }}</h1>
            @if($theme->start && $theme->end)
                <p class="text-base-content/60">
                    {{ $theme->start->format('d M') }} - {{ $theme->end->format('d M Y') }}
                </p>
            @endif
        </header>

        @if($theme->description)
            <div class="prose max-w-none mb-8">
                <p>{{ $theme->description }}</p>
            </div>
        @endif

        @if($theme->activities->isNotEmpty())
            <h2 class="text-xl mb-4">Activiteiten bij dit thema</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($theme->activities as $activity)
                    <x-activity-card :activity="$activity" />
                @endforeach
            </div>
        @else
            <p class="text-base-content/60">Nog geen activiteiten gekoppeld aan dit thema.</p>
        @endif
    </div>
</x-layout>
