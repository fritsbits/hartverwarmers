<x-layout title="Mijn bookmarks">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Breadcrumb -->
        <nav class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('profile.show') }}">Profiel</a></li>
                <li>Bookmarks</li>
            </ul>
        </nav>

        <h1 class="text-3xl font-bold mb-8">Mijn bookmarks</h1>

        @if($activities->isEmpty())
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <p class="text-base-content/60 mb-4">Je hebt nog geen activiteiten gebookmarkt.</p>
                <a href="{{ route('activities.index') }}" class="btn btn-primary">
                    Ontdek activiteiten
                </a>
            </div>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($activities as $activity)
                    <x-activity-card :activity="$activity" />
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
