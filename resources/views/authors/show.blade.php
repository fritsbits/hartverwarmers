<x-layout :title="$author->name">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <nav class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('authors.index') }}">Bijdragers</a></li>
                <li>{{ $author->name }}</li>
            </ul>
        </nav>

        <div class="flex flex-col md:flex-row gap-8 items-start">
            <!-- Author Info -->
            <div class="md:w-64 text-center">
                @if($author->image)
                    <div class="avatar">
                        <div class="w-32 rounded-full">
                            <img src="{{ $author->image }}" alt="{{ $author->name }}">
                        </div>
                    </div>
                @else
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-32">
                            <span class="text-4xl">{{ substr($author->name, 0, 1) }}</span>
                        </div>
                    </div>
                @endif

                <h1 class="text-2xl mt-4">{{ $author->name }}</h1>
                @if($author->title)
                    <p class="text-base-content/60">{{ $author->title }}</p>
                @endif
                @if($author->company)
                    <p class="font-medium mt-2">
                        @if($author->company_link)
                            <a href="{{ $author->company_link }}" target="_blank" class="link">{{ $author->company }}</a>
                        @else
                            {{ $author->company }}
                        @endif
                    </p>
                @endif

                @if($author->linkedin)
                    <a href="{{ $author->linkedin }}" target="_blank" class="btn btn-outline btn-sm mt-4">
                        LinkedIn
                    </a>
                @endif
            </div>

            <!-- Description -->
            <div class="flex-1">
                @if($author->description)
                    <div class="prose max-w-none">
                        {!! $author->description !!}
                    </div>
                @else
                    <p class="text-base-content/60">Nog geen beschrijving beschikbaar.</p>
                @endif
            </div>
        </div>
    </div>
</x-layout>
