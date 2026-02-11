<x-layout title="Themakalender">
    <div class="container mx-auto px-6 py-12">
        <div class="intro-block py-8">
            <h1>Themakalender</h1>
            <p>Ontdek thema's, feestdagen en speciale momenten.</p>
        </div>

        <div class="flex justify-between items-center mb-8">
            <flux:button variant="ghost" href="{{ $previousUrl }}" icon="chevron-left">
                Vorige
            </flux:button>
            <flux:button variant="ghost" href="{{ $nextUrl }}" icon-trailing="chevron-right">
                Volgende
            </flux:button>
        </div>

        @if($themesByMonth->isEmpty())
            <div class="text-center py-12">
                <flux:text class="text-[var(--color-text-secondary)]">Geen thema's in deze periode.</flux:text>
            </div>
        @else
            <div class="space-y-8">
                @foreach($themesByMonth as $monthKey => $themes)
                    @php
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->locale('nl');
                    @endphp
                    <section>
                        <flux:heading size="lg" class="mb-4 text-[var(--color-primary)]">
                            {{ ucfirst($monthDate->translatedFormat('F Y')) }}
                        </flux:heading>
                        <flux:card class="!p-0">
                            <ul class="divide-y divide-[var(--color-border-light)]">
                                @foreach($themes as $theme)
                                    <li class="flex items-center gap-4 px-4 py-3">
                                        <span class="text-sm font-medium text-[var(--color-text-secondary)] w-16 shrink-0">
                                            {{ $theme->start->locale('nl')->translatedFormat('d M') }}
                                        </span>
                                        <span class="font-medium">{{ $theme->title }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </flux:card>
                    </section>
                @endforeach
            </div>
        @endif

        <div class="flex justify-between items-center mt-8">
            <flux:button variant="ghost" href="{{ $previousUrl }}" icon="chevron-left">
                Vorige
            </flux:button>
            <flux:button variant="ghost" href="{{ $nextUrl }}" icon-trailing="chevron-right">
                Volgende
            </flux:button>
        </div>
    </div>
</x-layout>
