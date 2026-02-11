<x-layout title="Bijdragers">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="intro-block py-8">
            <h1>Onze bijdragers</h1>
            <p>Ontmoet de activiteitenbegeleiders die hun kennis en ervaring delen.</p>
        </div>

        @if($authors->isEmpty())
            <div class="text-center py-12">
                <flux:text class="text-[var(--color-text-secondary)]">Nog geen bijdragers.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($authors as $author)
                    <a href="{{ route('authors.show', $author) }}">
                        <flux:card class="hover:shadow-md transition-shadow text-center">
                            @if($author->image)
                                <div class="flex justify-center mb-4">
                                    <img src="{{ $author->image }}" alt="{{ $author->name }}" class="w-20 h-20 rounded-full object-cover">
                                </div>
                            @else
                                <div class="flex justify-center mb-4">
                                    <div class="w-20 h-20 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl font-semibold">
                                        {{ substr($author->name, 0, 1) }}
                                    </div>
                                </div>
                            @endif

                            <flux:heading size="lg" class="mt-4">{{ $author->name }}</flux:heading>
                            @if($author->title)
                                <flux:text class="text-[var(--color-text-secondary)] text-sm">{{ $author->title }}</flux:text>
                            @endif
                            @if($author->company)
                                <flux:text class="text-sm">{{ $author->company }}</flux:text>
                            @endif
                        </flux:card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
