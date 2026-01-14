<x-layout title="Bijdragers">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="intro-block py-8">
            <h1>Onze bijdragers</h1>
            <p>Ontmoet de activiteitenbegeleiders die hun kennis en ervaring delen.</p>
        </div>

        @if($authors->isEmpty())
            <div class="text-center py-12">
                <p class="text-base-content/60">Nog geen bijdragers.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($authors as $author)
                    <a href="{{ route('authors.show', $author) }}" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="card-body items-center text-center">
                            @if($author->image)
                                <div class="avatar">
                                    <div class="w-20 rounded-full">
                                        <img src="{{ $author->image }}" alt="{{ $author->name }}">
                                    </div>
                                </div>
                            @else
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-full w-20">
                                        <span class="text-2xl">{{ substr($author->name, 0, 1) }}</span>
                                    </div>
                                </div>
                            @endif

                            <h2 class="card-title mt-4">{{ $author->name }}</h2>
                            @if($author->title)
                                <p class="text-base-content/60 text-sm">{{ $author->title }}</p>
                            @endif
                            @if($author->company)
                                <p class="text-sm">{{ $author->company }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
