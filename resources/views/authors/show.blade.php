<x-layout :title="$author->name">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('authors.index') }}">Bijdragers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $author->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col md:flex-row gap-8 items-start">
            <!-- Author Info -->
            <div class="md:w-64 text-center">
                @if($author->image)
                    <div class="flex justify-center">
                        <img src="{{ $author->image }}" alt="{{ $author->name }}" class="w-32 h-32 rounded-full object-cover">
                    </div>
                @else
                    <div class="flex justify-center">
                        <div class="w-32 h-32 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-4xl font-semibold">
                            {{ substr($author->name, 0, 1) }}
                        </div>
                    </div>
                @endif

                <h1 class="text-2xl mt-4">{{ $author->name }}</h1>
                @if($author->title)
                    <flux:text class="text-[var(--color-text-secondary)]">{{ $author->title }}</flux:text>
                @endif
                @if($author->company)
                    <p class="font-medium mt-2">
                        @if($author->company_link)
                            <flux:link href="{{ $author->company_link }}" target="_blank">{{ $author->company }}</flux:link>
                        @else
                            {{ $author->company }}
                        @endif
                    </p>
                @endif

                @if($author->linkedin)
                    <flux:button variant="ghost" href="{{ $author->linkedin }}" target="_blank" class="mt-4">
                        LinkedIn
                    </flux:button>
                @endif
            </div>

            <!-- Description -->
            <div class="flex-1">
                @if($author->description)
                    <div class="prose max-w-none">
                        {!! $author->description !!}
                    </div>
                @else
                    <flux:text class="text-[var(--color-text-secondary)]">Nog geen beschrijving beschikbaar.</flux:text>
                @endif
            </div>
        </div>
    </div>
</x-layout>
